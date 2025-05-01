import { Button } from "@hinddy/ui/button";
import { Text } from "@hinddy/ui/text";
import { Badge } from "@hinddy/ui/badge";
import { Card } from "@hinddy/components/card";
import { Hero } from "@hinddy/blocks/hero";

function App() {
  return (
    <div className="min-h-screen bg-background text-foreground">
      {/* Hero section */}
      <Hero
        variant="gradient"
        size="lg"
        title="Добро пожаловать в WP Fasty"
        description="Современный WordPress фреймворк с компонентным подходом и мощными инструментами для разработки"
        buttons={
          <div>
            <Button variant="primary" size="lg" className="mr-4">Начать работу</Button>
            <Button variant="outline" size="lg">Документация</Button>
          </div>
        }
      />

      {/* Features section */}
      <section className="py-16">
        <div className="container mx-auto px-4">
          <Text variant="title" size="xl" as="h2" className="text-center mb-12">
            Особенности фреймворка
          </Text>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
            <Card
              variant="default"
              title="Компонентный подход"
              description="Модульная архитектура с переиспользуемыми UI компонентами"
              badges={[{ text: "UI", variant: "default" }]}
              elevation="md"
              radius="lg"
            >
              <Text className="mt-4">
                Разработка с использованием современных компонентов и блоков ускоряет
                создание сайтов и упрощает поддержку проектов.
              </Text>
            </Card>

            <Card
              variant="primary"
              title="Интеграция с Tailwind"
              description="Мощные стили на основе utility-first подхода"
              badges={[{ text: "CSS", variant: "secondary" }]}
              elevation="md"
              radius="lg"
            >
              <Text className="mt-4 text-primary-foreground">
                Tailwind CSS позволяет быстро создавать красивые интерфейсы
                без написания кастомных стилей.
              </Text>
            </Card>

            <Card
              variant="outline"
              title="Шаблонизатор Latte"
              description="Высокопроизводительные и легко поддерживаемые шаблоны"
              badges={[{ text: "Templates", variant: "destructive" }]}
              elevation="md"
              radius="lg"
            >
              <Text className="mt-4">
                Latte предоставляет чистый синтаксис для шаблонов и встроенную
                систему кэширования для максимальной производительности.
              </Text>
            </Card>
          </div>
        </div>
      </section>

      {/* Testimonials section */}
      <section className="py-16 bg-muted/30">
        <div className="container mx-auto px-4">
          <Text variant="title" size="xl" as="h2" className="text-center mb-12">
            Отзывы разработчиков
          </Text>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
            <Card
              variant="ghost"
              size="lg"
              elevation="sm"
            >
              <div className="flex items-start space-x-4">
                <div className="w-12 h-12 rounded-full bg-primary flex items-center justify-center text-primary-foreground">
                  <Text weight="bold">АК</Text>
                </div>
                <div>
                  <Text weight="bold">Алексей Киреев</Text>
                  <Text variant="muted" size="sm" className="mb-4">Ведущий разработчик</Text>
                  <Text>
                    "WP Fasty кардинально изменил мой подход к разработке WordPress сайтов.
                    Компонентная структура и интеграция с современными инструментами сделали процесс
                    более организованным и эффективным."
                  </Text>
                </div>
              </div>
            </Card>

            <Card
              variant="ghost"
              size="lg"
              elevation="sm"
            >
              <div className="flex items-start space-x-4">
                <div className="w-12 h-12 rounded-full bg-secondary flex items-center justify-center text-secondary-foreground">
                  <Text weight="bold">МС</Text>
                </div>
                <div>
                  <Text weight="bold">Мария Смирнова</Text>
                  <Text variant="muted" size="sm" className="mb-4">Front-end разработчик</Text>
                  <Text>
                    "Наконец-то WordPress фреймворк, в котором приятно работать. Семантические
                    компоненты, интеграция с Tailwind и всё это отлично работает с WordPress.
                    Сокращает время разработки на 40%."
                  </Text>
                </div>
              </div>
            </Card>
          </div>
        </div>
      </section>

      {/* CTA section */}
      <Hero
        variant="dark"
        size="sm"
        title="Готовы начать?"
        description="Присоединяйтесь к сообществу разработчиков и создавайте современные WordPress сайты"
        buttons={
          <Button variant="primary" size="lg">Скачать фреймворк</Button>
        }
      />

      {/* Footer */}
      <footer className="bg-card py-12">
        <div className="container mx-auto px-4">
          <div className="flex flex-col md:flex-row justify-between items-center">
            <div className="mb-6 md:mb-0">
              <Text size="lg" weight="bold">WP Fasty</Text>
              <Text variant="muted" size="sm">Современный WordPress фреймворк</Text>
            </div>

            <div className="flex space-x-4">
              <Badge variant="secondary">v1.0.0</Badge>
              <Badge variant="outline">WordPress</Badge>
              <Badge variant="default">React</Badge>
              <Badge variant="default">Tailwind</Badge>
            </div>
          </div>

          <div className="border-t border-border mt-8 pt-8 text-center">
            <Text variant="muted" size="sm">
              © 2023 WP Fasty. Все права защищены.
            </Text>
          </div>
        </div>
      </footer>
    </div>
  )
}

export default App;