<!DOCTYPE html>
<html lang="ru" class="dark-mode">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Топ 100 Инфобизнесменов России</title>
  <meta name="description"
    content="Рейтинг самых успешных и влиятельных предпринимателей в сфере информационного бизнеса">
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <link rel="stylesheet" href="http://localhost/wp-content/themes/wp-fasty/wp-fasty-oop/theme.min.css">

  <script>
    if (localStorage.getItem('darkMode') === 'true' ||
      (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
      document.documentElement.classList.add('dark-mode');
      document.documentElement.style.colorScheme = 'dark-mode';
    } else {
      document.documentElement.classList.remove('dark-mode');
      document.documentElement.style.colorScheme = 'light';
    }
    
    function toggleDarkMode() {
      const isDark = document.documentElement.classList.toggle('dark-mode');
      localStorage.setItem('darkMode', isDark);
      document.documentElement.style.colorScheme = isDark ? 'dark-mode' : 'light';
    }
  </script>
</head>

<body class="page-layout">
  <div class="page-container">
    <header class="site-header">
      <div class="s-index-div-container-flex-h-16-items-center-justify-between-px-6-mx-auto-px-4">
        <a class="site-logo" href="/">
          <i data-lucide="award" class="s-index-i-h-5-w-5"></i>
          <span>Топ 100 Инфобизнесменов</span>
        </a>
        <div class="main-search-container">
          <div class="s-index-div-relative-w-full">
            <i data-lucide="search" class="s-index-i-absolute-h-4-left-3-text-muted-foreground-top-25-w-4"></i>
            <input type="search"
              class="s-index-input-bg-accent-50-border-0-border-input-cursor-not-allowed-opacity-50-bg-transparent-border-0-font-medium-text-foreground-text-sm-flex-outline-none-ring-1-ring-offset-2-ring-ring-h-10-text-sm-pl-10-text-muted-foreground-px-3-py-2-ring-offset-background-rounded-full-text-base-w-full"
              placeholder="Поиск по имени...">
          </div>
        </div>
        <nav class="main-navigation"><a class="s-index-a-font-medium-text-muted-foreground-text-sm-transition-colors"
            href="/">Главная</a><a class="s-index-a-font-medium-text-muted-foreground-text-sm-transition-colors"
            href="#professionals">Рейтинг</a><a
            class="s-index-a-font-medium-text-muted-foreground-text-sm-transition-colors" href="/videos">Лента</a><a
            class="s-index-a-font-medium-text-muted-foreground-text-sm-transition-colors" href="#about">О проекте</a></nav>
        <div class="header-actions"><button onclick="toggleDarkMode()"
            class="dark-mode-toggle">
            <i data-lucide="sun"
              class="s-index-i-absolute-rotate-90-scale-0-duration-100-h-12rem-rotate-0-scale-100-transition-all-w-12rem"></i>
            <i data-lucide="moon"
              class="s-index-i-absolute-rotate-0-scale-100-duration-100-h-12rem-rotate-90-scale-0-transition-all-w-12rem"></i>
            <span class="s-index-span-sr-only">Переключить тему</span>
          </button><button
            id="mobileMenuButton"
            class="s-index-button-pointer-events-none-shrink-0-size-4-bg-accent-border-0-border-input-opacity-50-pointer-events-none-outline-none-ring-2-ring-offset-2-ring-ring-font-medium-gap-2-h-10-bg-accent-text-accent-foreground-inline-flex-items-center-justify-center-hidden-ring-offset-background-rounded-full-text-accent-foreground-text-sm-transition-colors-w-10-whitespace-nowrap">
            <span class="s-index-span-sr-only">Открыть меню</span>
            <i data-lucide="menu" class="s-index-i-h-5-w-5"></i>
          </button></div>
      </div>
      <div class="s-index-div-border-border-border-t-hidden-py-2">
        <div class="s-index-div-container-mx-auto-px-4-py-2">
          <div class="s-index-div-relative-w-full"><i data-lucide="search"
              class=" absolute left-3 top-2.5 h-4 w-4 text-muted-foreground"></i><input type="search"
              class="s-index-input-bg-accent-50-border-0-border-input-cursor-not-allowed-opacity-50-bg-transparent-border-0-font-medium-text-foreground-text-sm-flex-outline-none-ring-1-ring-offset-2-ring-ring-h-10-text-sm-pl-10-text-muted-foreground-px-3-py-2-ring-offset-background-rounded-full-text-base-w-full"
              placeholder="Поиск по имени..."></div>
        </div>
      </div>
    </header>
    <main class="main-content">
      <section class="about-section">
        <div class="s-index-div-absolute-bg-gradient-to-br-from-primary-10-inset-0-to-transparent-via-transparent"></div>
        <div class="s-index-div-container-px-6-mx-auto-px-4-relative">
          <div class="professionals-grid">
            <div class="hero-content">
              <h1 class="hero-title">
                Топ 100
                <span class="hero-title-highlight">Инфобизнесменов</span>
                России
              </h1>
              <p class="hero-subtitle">Рейтинг самых успешных и влиятельных
                предпринимателей в сфере информационного бизнеса</p>
              <div class="hero-cta-group">
                <a class="s-index-a-pointer-events-none-shrink-0-size-4-bg-primary-opacity-50-pointer-events-none-outline-none-ring-2-ring-offset-2-ring-ring-font-medium-gap-2-gradient-glow-h-11-bg-primary-90-inline-flex-items-center-justify-center-px-8-ring-offset-background-rounded-full-text-primary-foreground-text-sm-transition-colors-whitespace-nowrap"
                  href="#professionals">Смотреть рейтинг</a>
                <a class="s-index-a-pointer-events-none-shrink-0-size-4-bg-background-border-border-input-opacity-50-pointer-events-none-outline-none-ring-2-ring-offset-2-ring-ring-font-medium-gap-2-h-11-bg-accent-text-accent-foreground-inline-flex-items-center-justify-center-px-8-ring-offset-background-rounded-full-text-sm-transition-colors-whitespace-nowrap"
                  href="#about">Узнать больше</a>
              </div>
            </div>
            <div class="hero-illustration-container">
              <div
                class="s-index-div-aspect-4-3-gradient-border-max-w-500px-overflow-hidden-relative-rounded-3xl-shadow-2xl-w-full">
                <div
                  class="s-index-div-absolute-backdrop-blur-sm-bg-gradient-to-br-flex-from-background-20-inset-0-items-center-justify-center-to-muted-20">
                  <div class="s-index-div-gap-6-grid-grid-cols-2-p-8">
                    <div
                      class="s-index-div-backdrop-blur-sm-bg-background-90-border-border-border-50-bg-background-80-flex-flex-col-items-center-p-4-rounded-xl-shadow-lg">
                      <i data-lucide="users" class="s-index-i-h-8-mb-3-text-primary-w-8"></i>
                      <span class="s-index-span-font-medium-text-foreground-text-sm">100+ Профессионалов</span>
                    </div>
                    <div
                      class="s-index-div-backdrop-blur-sm-bg-background-90-border-border-border-50-bg-background-80-flex-flex-col-items-center-p-4-rounded-xl-shadow-lg">
                      <i data-lucide="trending-up" class="s-index-i-h-8-mb-3-text-primary-w-8"></i>
                      <span class="s-index-span-font-medium-text-foreground-text-sm">Актуальный рейтинг</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
      <section id="professionals" class="about-section">
        <div class="s-index-div-container-px-6-mx-auto-px-4">
          <div class="s-index-div-flex-flex-col-items-center-justify-center-mb-16-space-y-4-text-center">
            <div class="s-index-div-max-w-3xl-space-y-3">
              <h2 class="s-index-h2-font-medium-text-4xl-text-3xl-tracking-tight">Рейтинг инфобизнесменов</h2>
              <p class="s-index-p-text-lg-text-muted-foreground">Ознакомьтесь с нашим рейтингом самых успешных
                предпринимателей в сфере информационного бизнеса</p>
            </div>
          </div>
          <div id="businessmenContainer"
            class="news-feed">
          </div>
          <div class="professionals-cta"><a
              class="s-index-a-pointer-events-none-shrink-0-size-4-apple-button-apple-button-secondary-bg-background-border-border-input-opacity-50-pointer-events-none-outline-none-ring-2-ring-offset-2-ring-ring-font-medium-gap-2-h-11-bg-accent-text-accent-foreground-inline-flex-items-center-justify-center-px-8-ring-offset-background-rounded-full-text-sm-transition-colors-whitespace-nowrap"
              href="/all-professionals">Показать всех 100 инфобизнесменов</a></div>
        </div>
      </section>
      <section class="about-section">
        <div class="s-index-div-container-px-6-mx-auto-px-4">
          <div class="s-index-div-flex-flex-col-items-center-justify-center-mb-16-space-y-4-text-center">
            <div class="s-index-div-max-w-3xl-space-y-3">
              <h2 class="s-index-h2-font-medium-text-4xl-text-3xl-tracking-tight">Лента инфобиза</h2>
              <p class="s-index-p-text-lg-text-muted-foreground">Последние видео от ведущих инфобизнесменов
                России</p>
            </div>
          </div>
          <div id="newsContainer" class="news-feed">
          </div>
          <div class="professionals-cta"><a
              class="s-index-a-pointer-events-none-shrink-0-size-4-apple-button-apple-button-secondary-bg-background-border-border-input-opacity-50-pointer-events-none-outline-none-ring-2-ring-offset-2-ring-ring-font-medium-gap-2-h-11-bg-accent-text-accent-foreground-inline-flex-items-center-justify-center-px-8-ring-offset-background-rounded-full-text-sm-transition-colors-whitespace-nowrap"
              href="/videos">Смотреть все видео</a></div>
        </div>
      </section>
      <section id="about" class="s-index-section-bg-muted-py-32-py-24-w-full">
        <div class="s-index-div-container-px-6-mx-auto-px-4">
          <div class="s-index-div-flex-flex-col-items-center-justify-center-mb-16-space-y-4-text-center">
            <div class="s-index-div-max-w-3xl-space-y-3">
              <h2 class="s-index-h2-font-medium-text-4xl-text-3xl-tracking-tight">О проекте</h2>
              <p class="s-index-p-text-lg-text-muted-foreground">Наш рейтинг составлен на основе объективных показателей
                успешности</p>
            </div>
          </div>
          <div class="about-content">
            <div class="s-index-div-space-y-5">
              <div class="s-index-div-bg-accent-inline-block-px-3-py-1-rounded-full-text-accent-foreground-text-sm">Методология
              </div>
              <h3 class="s-index-h3-font-medium-text-2xl">Как мы составляем рейтинг</h3>
              <p class="s-index-p-leading-relaxed-text-lg-text-muted-foreground">Наш рейтинг основан на комплексной оценке
                различных факторов, включая доход, охват аудитории, влияние в социальных сетях,
                качество контента и отзывы клиентов. Мы регулярно обновляем данные, чтобы предоставить вам самую
                актуальную информацию о лидерах инфобиза.</p>
            </div>
            <div class="s-index-div-space-y-5">
              <div class="s-index-div-bg-accent-inline-block-px-3-py-1-rounded-full-text-accent-foreground-text-sm">Критерии</div>
              <h3 class="s-index-h3-font-medium-text-2xl">Что мы оцениваем</h3>
              <ul class="s-index-ul-gap-3-grid-text-lg-text-muted-foreground">
                <li class="s-index-li-flex-gap-3-items-center">
                  <div class="s-index-div-bg-accent-p-1-rounded-full-text-accent-foreground">
                    <i data-lucide="check" class="s-index-i-h-4-w-4"></i>
                  </div>Годовой доход от информационных продуктов
                </li>
                <li class="s-index-li-flex-gap-3-items-center">
                  <div class="s-index-div-bg-accent-p-1-rounded-full-text-accent-foreground">
                    <i data-lucide="check" class="s-index-i-h-4-w-4"></i>
                  </div>Размер и вовлеченность аудитории
                </li>
                <li class="s-index-li-flex-gap-3-items-center">
                  <div class="s-index-div-bg-accent-p-1-rounded-full-text-accent-foreground">
                    <i data-lucide="check" class="s-index-i-h-4-w-4"></i>
                  </div>Качество и уникальность контента
                </li>
                <li class="s-index-li-flex-gap-3-items-center">
                  <div class="s-index-div-bg-accent-p-1-rounded-full-text-accent-foreground">
                    <i data-lucide="check" class="s-index-i-h-4-w-4"></i>
                  </div>Отзывы и удовлетворенность клиентов
                </li>
              </ul>
            </div>
          </div>
        </div>
      </section>
    </main>
    <footer class="s-index-footer-border-border-border-t-py-12">
      <div class="s-index-div-container-px-6-mx-auto-px-4">
        <div class="s-index-div-flex-flex-col-gap-6-items-center-justify-between-flex-row">
          <div class="s-index-div-flex-gap-2-items-center"><i data-lucide="circle" class="s-index-i-h-5-w-5"></i><span
              class="s-index-span-font-medium-text-lg">Топ 100 Инфобизнесменов</span></div>
          <p class="s-index-p-text-left-text-center-text-muted-foreground-text-sm">2025 Топ 100 Инфобизнесменов. Все права
            защищены.</p>
          <div class="s-index-div-flex-gap-6"><a
              class="s-index-a-font-medium-text-foreground-text-muted-foreground-text-sm-transition-colors"
              href="#">Политика конфиденциальности</a><a
              class="s-index-a-font-medium-text-foreground-text-muted-foreground-text-sm-transition-colors" href="#">Условия
              использования</a></div>
        </div>
      </div>
    </footer>
  </div>
  <div id="mobileMenu" 
    class="s-index-div-backdrop-blur-sm-bg-background-80-duration-300-fixed-inset-0-opacity-0-pointer-events-none-transition-opacity-z-50"
    aria-hidden="true">
    <div class="s-index-div-bottom-0-fixed-inset-x-0-mt-auto-z-50">
      <div class="s-index-div-duration-300-transition-transform-translate-y-full" data-state="closed">
        <div class="s-index-div-bg-background-border-border-border-t-flex-flex-col-h-calc-100vh-4rem-relative-rounded-t-125rem">
          <div class="s-index-div-bg-muted-h-2-mt-4-mx-auto-rounded-full-w-100px"></div>
          <div class="s-index-div-p-6-pt-8">
            <nav class="s-index-nav-gap-6-grid">
              <a class="s-index-a-flex-font-medium-gap-2-group-bg-accent-text-accent-foreground-items-center-px-3-py-2-rounded-md-text-base-transition-colors-w-full" href="/">
                <i data-lucide="home" class="s-index-i-h-5-w-5"></i>
                Главная
              </a>
              <a class="s-index-a-flex-font-medium-gap-2-group-bg-accent-text-accent-foreground-items-center-px-3-py-2-rounded-md-text-base-transition-colors-w-full" href="#professionals">
                <i data-lucide="users" class="s-index-i-h-5-w-5"></i>
                Рейтинг
              </a>
              <a class="s-index-a-flex-font-medium-gap-2-group-bg-accent-text-accent-foreground-items-center-px-3-py-2-rounded-md-text-base-transition-colors-w-full" href="/videos">
                <i data-lucide="play" class="s-index-i-h-5-w-5"></i>
                Лента
              </a>
              <a class="s-index-a-flex-font-medium-gap-2-group-bg-accent-text-accent-foreground-items-center-px-3-py-2-rounded-md-text-base-transition-colors-w-full" href="#about">
                <i data-lucide="info" class="s-index-i-h-5-w-5"></i>
                О проекте
              </a>
            </nav>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script>
    const businessmenData = {
      "topBusinessmen": [
        {
          "rank": 1,
          "name": "Александр Борисов",
          "revenue": "412M₽",
          "subscribers": "1.2M",
          "category": "Маркетинг",
          "image": "https://v0-topchik-infobiza.vercel.app/placeholder.svg?height=400&width=400",
          "tags": ["Маркетинг", "Продажи", "Консалтинг"],
          "socialLinks": {
            "website": "https://example.com",
            "instagram": "https://instagram.com",
            "youtube": "https://youtube.com"
          },
          "profileUrl": "/professional/1"
        },
        {
          "rank": 2,
          "name": "Елена Смирнова",
          "revenue": "385M₽",
          "subscribers": "980K",
          "category": "Финансы",
          "image": "https://v0-topchik-infobiza.vercel.app/placeholder.svg?height=400&width=400",
          "tags": ["Финансы", "Инвестиции", "Трейдинг"],
          "socialLinks": {
            "website": "https://example.com",
            "instagram": "https://instagram.com",
            "youtube": "https://youtube.com"
          },
          "profileUrl": "/professional/2"
        },
        {
          "rank": 3,
          "name": "Дмитрий Петров",
          "revenue": "350M₽",
          "subscribers": "850K",
          "category": "Разработка",
          "image": "https://v0-topchik-infobiza.vercel.app/placeholder.svg?height=400&width=400",
          "tags": ["Разработка", "IT", "Программирование"],
          "socialLinks": {
            "website": "https://example.com",
            "instagram": "https://instagram.com",
            "youtube": "https://youtube.com"
          },
          "profileUrl": "/professional/3"
        },
        {
          "rank": 4,
          "name": "Анна Иванова",
          "revenue": "320M₽",
          "subscribers": "780K",
          "category": "Маркетинг",
          "image": "https://v0-topchik-infobiza.vercel.app/placeholder.svg?height=400&width=400",
          "tags": ["Маркетинг", "Продажи", "Консалтинг"],
          "socialLinks": {
            "website": "https://example.com",
            "instagram": "https://instagram.com",
            "youtube": "https://youtube.com"
          },
          "profileUrl": "/professional/4"
        },
        {
          "rank": 5,
          "name": "Александр Борисов",
          "revenue": "300M₽",
          "subscribers": "750K",
          "category": "Маркетинг",
          "image": "https://v0-topchik-infobiza.vercel.app/placeholder.svg?height=400&width=400",
          "tags": ["Маркетинг", "Продажи", "Консалтинг"],
          "socialLinks": {
            "website": "https://example.com",
            "instagram": "https://instagram.com",
            "youtube": "https://youtube.com"
          },
          "profileUrl": "/professional/5"
        },
        {
          "rank": 6,
          "name": "Елена Смирнова",
          "revenue": "280M₽",
          "subscribers": "700K",
          "category": "Финансы",
          "image": "https://v0-topchik-infobiza.vercel.app/placeholder.svg?height=400&width=400",
          "tags": ["Финансы", "Инвестиции", "Трейдинг"],
          "socialLinks": {
            "website": "https://example.com",
            "instagram": "https://instagram.com",
            "youtube": "https://youtube.com"
          },
          "profileUrl": "/professional/6"
        },
        {
          "rank": 7,
          "name": "Дмитрий Петров",
          "revenue": "250M₽",
          "subscribers": "650K",
          "category": "Разработка",
          "image": "https://v0-topchik-infobiza.vercel.app/placeholder.svg?height=400&width=400",
          "tags": ["Разработка", "IT", "Программирование"],
          "socialLinks": {
            "website": "https://example.com",
            "instagram": "https://instagram.com",
            "youtube": "https://youtube.com"
          },
          "profileUrl": "/professional/7"
        },
        {
          "rank": 8,
          "name": "Анна Иванова",
          "revenue": "220M₽",
          "subscribers": "600K",
          "category": "Маркетинг",
          "image": "https://v0-topchik-infobiza.vercel.app/placeholder.svg?height=400&width=400",
          "tags": ["Маркетинг", "Продажи", "Консалтинг"],
          "socialLinks": {
            "website": "https://example.com",
            "instagram": "https://instagram.com",
            "youtube": "https://youtube.com"
          },
          "profileUrl": "/professional/8"
        }
      ],

      "infoBusinessNews": [
        {
          "date": "2024-03-20",
          "title": "Как создать личный бренд в Instagram и монетизировать его",
          "description": "Как искусственный интеллект меняет подход к обучению",
          "author": "Мария Козлова",
          "authorImage": "https://v0-topchik-infobiza.vercel.app/placeholder.svg?height=100&width=100",
          "authorUrl": "/professional/4",
          "image": "https://v0-topchik-infobiza.vercel.app/placeholder.svg?height=720&width=1280",
          "duration": "15:18",
          "platform": "YouTube",
          "views": "203K",
          "publishedAt": "2 недели назад",
          "url": "#"
        },
        {
          "date": "2024-03-20",
          "title": "Как создать личный бренд в Instagram и монетизировать его",
          "description": "Как искусственный интеллект меняет подход к обучению",
          "author": "Мария Козлова",
          "authorImage": "https://v0-topchik-infobiza.vercel.app/placeholder.svg?height=100&width=100",
          "authorUrl": "/professional/4",
          "image": "https://v0-topchik-infobiza.vercel.app/placeholder.svg?height=720&width=1280",
          "duration": "15:18",
          "platform": "YouTube",
          "views": "203K",
          "publishedAt": "2 недели назад",  
          "url": "#"
        },
        {
          "date": "2024-03-20",
          "title": "Как создать личный бренд в Instagram и монетизировать его",
          "description": "Как искусственный интеллект меняет подход к обучению",
          "author": "Мария Козлова",
          "authorImage": "https://v0-topchik-infobiza.vercel.app/placeholder.svg?height=100&width=100",
          "authorUrl": "/professional/4",
          "image": "https://v0-topchik-infobiza.vercel.app/placeholder.svg?height=720&width=1280",
          "duration": "15:18",
          "platform": "YouTube",
          "views": "203K",  
          "publishedAt": "2 недели назад",
          "url": "#"
        },
        {
          "date": "2024-03-20",
          "title": "Как создать личный бренд в Instagram и монетизировать его",
          "description": "Как искусственный интеллект меняет подход к обучению",
          "author": "Мария Козлова",
          "authorImage": "https://v0-topchik-infobiza.vercel.app/placeholder.svg?height=100&width=100",
          "authorUrl": "/professional/4",
          "image": "https://v0-topchik-infobiza.vercel.app/placeholder.svg?height=720&width=1280",
          "duration": "15:18",
          "platform": "YouTube",
          "views": "203K",
          "publishedAt": "2 недели назад",
          "url": "#"
        }
      ]
    };
    
    const cardTemplates = new Map([
      ['businessman', (data) => `
        <div class="s-index-div-bg-background-border-border-border-duration-100-ease-in-out-group-translate-y-1-shadow-xl-overflow-hidden-rounded-xl-shadow-sm-text-foreground-transform-transition-all">
          <div class="s-index-div-h-56-overflow-hidden-relative-rounded-t-xl-w-full">
            <div class="s-index-div-absolute-left-4-top-4-z-10">
              <div class="s-index-div-backdrop-blur-sm-bg-background-90-bg-background-font-medium-px-3-py-1-rounded-full-text-foreground-text-xs-transition-colors">
                #${data.rank}
              </div>
            </div>
            <img alt="${data.name}" 
              src="${data.image}" 
              class="s-index-img-duration-500-scale-105-h-full-object-cover-transition-transform-w-full"
            />
          </div>
          <div class="s-index-div-p-4">
            <h3 class="s-index-h3-font-medium-text-foreground-text-xl">${data.name}</h3>
            <p class="s-index-p-mt-1-text-muted-foreground-text-sm">${data.category}</p>
            <div class="s-index-div-gap-4-grid-grid-cols-2-mt-5">
              <div>
                <p class="s-index-p-text-muted-foreground-text-xs-tracking-wide-uppercase">Годовой доход</p>
                <p class="s-index-p-font-medium-mt-1-text-foreground">${data.revenue}</p>
              </div>
              <div>
                <p class="s-index-p-text-muted-foreground-text-xs-tracking-wide-uppercase">Аудитория</p>
                <p class="s-index-p-font-medium-mt-1-text-foreground">${data.subscribers}</p>
              </div>
            </div>
            <div class="s-index-div-flex-flex-wrap-gap-2-mt-5">
              ${data.tags.map(tag => `
                <span class="s-index-span-bg-accent-font-medium-bg-accent-80-inline-flex-items-center-px-25-py-05-rounded-full-text-accent-foreground-text-xs-transition-colors">
                  ${tag}
                </span>
              `).join('')}
            </div>
          </div>
          <div class="s-index-div-flex-items-center-justify-between-p-4-pt-0">
            <div class="s-index-div-flex-gap-2">
              ${Object.entries(data.socialLinks).map(([platform, url]) => `
                <a href="${url}" 
                  target="_blank" 
                  rel="noopener noreferrer" 
                  class="s-index-a-bg-accent-h-9-bg-accent-80-inline-flex-items-center-justify-center-rounded-full-text-accent-foreground-transition-colors-w-9"
                >
                  <span class="s-index-span-sr-only">${platform}</span>
                  ${getSocialIcon(platform)}
                </a>
              `).join('')}
            </div>
            <a href="${data.profileUrl}" 
              class="s-index-a-border-border-input-font-medium-h-9-bg-accent-text-accent-foreground-inline-flex-items-center-justify-center-px-4-rounded-full-text-sm-transition-colors"
            >
              Подробнее
            </a>
          </div>
        </div>
      `],
      ['news', (data) => `
        <div class="news-card-container">
          <div class="news-card-image-container">
            <div class="news-card-hover-background"></div>
            <div class="news-card-hover-overlay">
              <button class="news-card-action-button">
                <i data-lucide="play" class="news-card-action-icon"></i>
                <span class="s-index-span-sr-only">Смотреть видео</span>
              </button>
            </div>
            <div class="news-card-duration-badge">
              ${data.duration || '15:18'}
            </div>
            <div class="news-card-tag-container">
              <div class="news-tag-badge">
                <i data-lucide="youtube" class="news-tag-icon"></i>
                <span>${data.platform || 'YouTube'}</span>
              </div>
            </div>
            <img 
              alt="${data.title}" 
              loading="lazy"
              decoding="async"
              class="news-card-image-hover-effect"
              src="${data.image}"
            >
          </div>
          <div class="news-card-content-padding">
            <a class="news-card-link" href="${data.url || '#'}">
              <h3 class="s-index-h3-font-medium-text-primary-line-clamp-2-text-foreground-text-lg-transition-colors">
                ${data.title}
              </h3>
            </a>
            <div class="s-index-div-flex-items-center-justify-between-mt-4">
              <div class="s-index-div-flex-gap-2-items-center">
                <span class="s-index-span-flex-h-8-overflow-hidden-relative-ring-1-ring-border-rounded-full-shrink-0-w-8">
                  <img 
                    class="s-index-img-aspect-square-h-full-w-full" 
                    alt="${data.author}"
                    src="${data.authorImage || 'https://v0-topchik-infobiza.vercel.app/placeholder.svg?height=100&width=100'}"
                  >
                </span>
                <a class="news-author-link" href="${data.authorUrl || '#'}">
                  ${data.author}
                </a>
              </div>
            </div>
          </div>
          <div class="news-card-footer">
            <div class="s-index-div-flex-gap-2-items-center">
              <span>${data.views || '203K'} просмотров</span>
              <span>•</span>
              <span>${data.publishedAt || '2 недели назад'}</span>
            </div>
          </div>
        </div>
      `]
    ]);
    
    function getSocialIcon(platform) {
      const iconMap = {
        website: 'external-link',
        instagram: 'instagram',
        youtube: 'youtube'
      };

      return `<i data-lucide="${iconMap[platform]}" class="s-index-i-h-4-w-4"></i>`;
    }
    
    function renderCards(containerId, dataArray, templateType) {
      const container = document.getElementById(containerId);
      if (!container) return;

      const template = cardTemplates.get(templateType);
      container.innerHTML = dataArray.map(item => template(item)).join('');
    }
    
    document.addEventListener('DOMContentLoaded', () => {
      const searchInput = document.querySelector('input[type="search"]');
      
      function filterBusinessmen(query) {
        if (!query) {
          renderCards('businessmenContainer', businessmenData.topBusinessmen, 'businessman');
          return;
        }
        
        const q = query.toLowerCase();
        const filtered = businessmenData.topBusinessmen
          .filter(item => item.name.toLowerCase().includes(q))
          .sort((a, b) => {
            const aExact = a.name.toLowerCase() === q;
            const bExact = b.name.toLowerCase() === q;
            return bExact - aExact;
          });
        
        renderCards('businessmenContainer', filtered, 'businessman');
      }
      
      let timeout;
      searchInput.addEventListener('input', (e) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => filterBusinessmen(e.target.value), 300);
      });
      
      renderCards('businessmenContainer', businessmenData.topBusinessmen, 'businessman');
      renderCards('newsContainer', businessmenData.infoBusinessNews, 'news');
      lucide.createIcons();
      
      const mobileMenu = document.getElementById('mobileMenu');
      const mobileMenuContent = mobileMenu.querySelector('[data-state]');
      const mobileMenuButton = document.getElementById('mobileMenuButton');
      let isOpen = false;

      function toggleMenu(open) {
        isOpen = open;
        
        if (open) {
          document.body.style.overflow = 'hidden';
          mobileMenu.classList.remove('opacity-0', 'pointer-events-none');
          mobileMenuContent.classList.remove('translate-y-full');
          mobileMenuContent.dataset.state = 'open';
        } else {
          document.body.style.overflow = '';
          mobileMenu.classList.add('opacity-0');
          mobileMenuContent.classList.add('translate-y-full');
          mobileMenuContent.dataset.state = 'closed';
          
          setTimeout(() => {
            if (!isOpen) {
              mobileMenu.classList.add('pointer-events-none');
            }
          }, 300);
        }
      }

      mobileMenuButton.addEventListener('click', () => toggleMenu(!isOpen));
      
      mobileMenu.addEventListener('click', (e) => {
        if (e.target === mobileMenu) {
          toggleMenu(false);
        }
      });
      
      mobileMenu.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => toggleMenu(false));
      });
      
      let touchStartY = 0;
      let touchEndY = 0;

      mobileMenuContent.addEventListener('touchstart', (e) => {
        touchStartY = e.touches[0].clientY;
      }, { passive: true });

      mobileMenuContent.addEventListener('touchmove', (e) => {
        touchEndY = e.touches[0].clientY;
        const diff = touchEndY - touchStartY;
        
        if (diff > 50) {
          toggleMenu(false);
        }
      }, { passive: true });
    });
  </script>
</body>

</html>