// go build -o siteTW main.go
package main

import (
	"embed"
	"encoding/json"
	"fmt"
	"html/template"
	"io"
	"io/fs"
	"log"
	"net/http"
	"os"
	"strings"
)

//go:embed public
var publicFS embed.FS

// WordPressContext представляет данные контекста WordPress
type WordPressContext struct {
	Site  map[string]interface{} `json:"site"`
	Page  map[string]interface{} `json:"page"`
	Query map[string]interface{} `json:"query"`
	Menu  map[string]interface{} `json:"menu"`
	Posts []interface{}          `json:"posts"`
}

func main() {
	// Проверяем режим запуска - сервер или рендеринг контента
	if len(os.Args) > 1 && os.Args[1] == "serve" {
		// Запускаем HTTP сервер
		serveMode()
	} else {
		// Запускаем в режиме рендеринга для WordPress
		renderMode()
	}
}

// Режим сервера - запускает самостоятельный HTTP-сервер
func serveMode() {
	log.Println("Запуск в режиме сервера...")

	// Create a sub-filesystem rooted at "public"
	staticFS, err := fs.Sub(publicFS, "public")
	if err != nil {
		log.Fatalf("Failed to create sub-filesystem: %v", err)
	}

	// Create a file server handler using the embedded filesystem.
	fileServer := http.FileServer(http.FS(staticFS))

	// Handle all requests by serving files from the embedded 'public' directory.
	http.Handle("/", fileServer)

	// Define the port.
	port := "8989"
	addr := ":" + port

	log.Printf("Serving embedded static files on http://localhost:%s\n", port)

	// Start the HTTP server.
	err = http.ListenAndServe(addr, nil)
	if err != nil {
		log.Fatalf("Failed to start server: %v", err)
	}
}

// Режим рендеринга - принимает контекст и возвращает HTML
func renderMode() {
	var contextJSON string

	// Проверяем, передан ли контекст как аргумент
	if len(os.Args) > 1 {
		contextJSON = os.Args[1]
	} else {
		// Если нет, пытаемся прочитать из stdin
		stdin, err := io.ReadAll(os.Stdin)
		if err != nil {
			log.Fatalf("Ошибка чтения из stdin: %v", err)
		}
		contextJSON = string(stdin)
	}

	// Если контекст пустой, используем фиктивные данные для отладки
	if contextJSON == "" {
		contextJSON = `{"site":{"title":"WordPress Site","url":"http://localhost"}}`
	}

	// Парсим JSON-контекст
	var context WordPressContext
	err := json.Unmarshal([]byte(contextJSON), &context)
	if err != nil {
		log.Fatalf("Ошибка парсинга JSON: %v", err)
	}

	// Получаем доступ к встроенным файлам
	templateFS, err := fs.Sub(publicFS, "public")
	if err != nil {
		log.Fatalf("Ошибка доступа к публичным файлам: %v", err)
	}

	// Читаем HTML шаблон
	templateContent, err := fs.ReadFile(templateFS, "index.html")
	if err != nil {
		log.Fatalf("Ошибка чтения шаблона: %v", err)
	}

	// Заменяем пути к статическим файлам на WordPress пути
	templateString := string(templateContent)
	templateString = strings.Replace(templateString, "css/", "<?php echo get_template_directory_uri(); ?>/goland/public/css/", -1)
	templateString = strings.Replace(templateString, "img/", "<?php echo get_template_directory_uri(); ?>/goland/public/img/", -1)
	templateString = strings.Replace(templateString, "js/", "<?php echo get_template_directory_uri(); ?>/goland/public/js/", -1)

	// Создаем и парсим шаблон
	tmpl, err := template.New("page").Parse(templateString)
	if err != nil {
		log.Fatalf("Ошибка парсинга шаблона: %v", err)
	}

	// Рендерим шаблон с данными из контекста
	var output strings.Builder
	err = tmpl.Execute(&output, context)
	if err != nil {
		log.Fatalf("Ошибка рендеринга шаблона: %v", err)
	}

	// Выводим HTML для встраивания
	fmt.Print(output.String())
}

// Функция для HTTP эндпоинта, который будет принимать контекст и возвращать HTML
func renderHTML(w http.ResponseWriter, r *http.Request) {
	if r.Method != "POST" {
		http.Error(w, "Expected POST request", http.StatusMethodNotAllowed)
		return
	}

	decoder := json.NewDecoder(r.Body)
	var context WordPressContext
	err := decoder.Decode(&context)
	if err != nil {
		http.Error(w, "Invalid JSON", http.StatusBadRequest)
		return
	}

	// Получаем доступ к встроенным файлам
	templateFS, err := fs.Sub(publicFS, "public")
	if err != nil {
		http.Error(w, "Template filesystem error", http.StatusInternalServerError)
		return
	}

	// Читаем HTML шаблон
	templateContent, err := fs.ReadFile(templateFS, "index.html")
	if err != nil {
		http.Error(w, "Template read error", http.StatusInternalServerError)
		return
	}

	// Создаем HTML шаблон
	tmpl, err := template.New("page").Parse(string(templateContent))
	if err != nil {
		http.Error(w, "Template parse error", http.StatusInternalServerError)
		return
	}

	// Рендерим шаблон с данными из контекста
	w.Header().Set("Content-Type", "text/html")
	err = tmpl.Execute(w, context)
	if err != nil {
		http.Error(w, "Template render error", http.StatusInternalServerError)
		return
	}
}
