# Go Frontend для WordPress

Этот модуль предоставляет интеграцию между WordPress темой WP Fasty и Go-приложением, которое рендерит фронтенд.

## Компиляция Go-приложения

### Для Windows

```bash
# Находясь в директории wp-fasty-ym/goland
go build -o siteTW.exe main.go
```

### Для Linux/macOS

```bash
# Находясь в директории wp-fasty-ym/goland
go build -o siteTW main.go
chmod +x siteTW
```

### Кросс-компиляция

Чтобы скомпилировать под другую ОС из Windows:

```bash
# Для Linux
set GOOS=linux
go build -o siteTW main.go

# Для macOS
set GOOS=darwin
go build -o siteTW main.go
```

Из Linux/macOS для Windows:

```bash
# Для Windows
GOOS=windows GOARCH=amd64 go build -o siteTW.exe main.go
```

## Структура каталогов

- `public/` - Статичные файлы для встраивания (HTML, CSS, изображения)
- `main.go` - Основной файл приложения

## Использование в WordPress

### Через шаблон страницы

1. Создайте новую страницу в WordPress
2. В правой панели выберите шаблон "Go Frontend"
3. Опубликуйте страницу

### Через шорткод

Вставьте шорткод `[go_frontend]` в содержимое любой страницы или поста.

### Программное использование

```php
<?php
// В любом шаблоне темы
do_action('wpfasty_go_frontend', [
    'cache' => true,         // Использовать кэширование
    'cache_time' => 3600     // Время кэширования в секундах
]);
?>
```

## Режимы запуска Go-приложения

### Режим сервера для разработки

```bash
# Запускает Go-приложение как HTTP-сервер на порту 8989
./siteTW serve
```

### Режим рендеринга для WordPress

```bash
# Передача контекста через stdin
echo '{"site":{"title":"Test"}}' | ./siteTW

# Передача контекста через параметр
./siteTW '{"site":{"title":"Test"}}'
```

## Структура контекста WordPress

Контекст WordPress передается в Go-приложение в формате JSON со следующей структурой:

```json
{
  "site": {
    "title": "Название сайта",
    "url": "https://example.com",
    "description": "Описание сайта",
    "language": "ru-RU",
    "charset": "UTF-8"
  },
  "page": {
    "id": 1,
    "title": "Заголовок страницы",
    "content": "Содержимое страницы",
    "excerpt": "Короткое описание",
    "permalink": "https://example.com/page",
    "modified": "2023-01-01T12:00:00+00:00",
    "thumbnail": {
      "url": "https://example.com/image.jpg",
      "alt": "Альтернативный текст"
    }
  },
  "query": {
    "is_single": false,
    "is_page": true,
    "is_archive": false,
    "is_home": false,
    "is_front_page": false
  },
  "menu": {
    "primary": [
      {
        "id": 1,
        "title": "Главная",
        "url": "https://example.com",
        "target": "",
        "current": true
      }
    ]
  }
}
```