<!DOCTYPE html>
<html lang="ru" className="html-dark">

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
      document.documentElement.classList.add('html-dark');
      document.documentElement.style.colorScheme = 'html-dark';
    } else {
      document.documentElement.classList.remove('html-dark');
      document.documentElement.style.colorScheme = 'light';
    }
    
    function toggleDarkMode() {
      const isDark = document.documentElement.classList.toggle('html-dark');
      localStorage.setItem('darkMode', isDark);
      document.documentElement.style.colorScheme = isDark ? 'html-dark' : 'light';
    }
  </script>
</head>

<body className="body-shadcn">
  <div className="s-index-div-flex-flex-col-min-h-screen">
    <header className="s-index-header-backdrop-blur-sm-border-b-border-border-50-sticky-top-0-z-50">
      <div className="s-index-div-container-flex-h-16-items-center-justify-between-px-6-mx-auto-px-4">
        <a className="s-index-a-flex-font-medium-gap-2-items-center-text-lg" href="/">
          <i data-lucide="award" className="s-index-i-h-5-w-5"></i>
          <span>Топ 100 Инфобизнесменов</span>
        </a>
        <div className="s-index-div-flex-1-hidden-max-w-md-flex-mx-4">
          <div className="s-index-div-relative-w-full">
            <i data-lucide="search" className="s-index-i-absolute-h-4-left-3-text-muted-foreground-top-25-w-4"></i>
            <input type="search"
              className="s-index-input-bg-accent-50-border-0-border-input-cursor-not-allowed-opacity-50-bg-transparent-border-0-font-medium-text-foreground-text-sm-flex-outline-none-ring-1-ring-offset-2-ring-ring-h-10-text-sm-pl-10-text-muted-foreground-px-3-py-2-ring-offset-background-rounded-full-text-base-w-full"
              placeholder="Поиск по имени...">
          </div>
        </div>
        <nav className="s-index-nav-gap-8-hidden-flex"><a className="s-index-a-font-medium-text-muted-foreground-text-sm-transition-colors"
            href="/">Главная</a><a className="s-index-a-font-medium-text-muted-foreground-text-sm-transition-colors"
            href="#professionals">Рейтинг</a><a
            className="s-index-a-font-medium-text-muted-foreground-text-sm-transition-colors" href="/videos">Лента</a><a
            className="s-index-a-font-medium-text-muted-foreground-text-sm-transition-colors" href="#about">О проекте</a></nav>
        <div className="s-index-div-flex-gap-4-items-center"><button onclick="toggleDarkMode()"
            className="s-index-button-bg-accent-h-10-bg-accent-80-inline-flex-items-center-justify-center-relative-rounded-full-text-accent-foreground-transition-colors-w-10">
            <i data-lucide="sun"
              className="s-index-i-absolute-rotate-90-scale-0-duration-100-h-12rem-rotate-0-scale-100-transition-all-w-12rem"></i>
            <i data-lucide="moon"
              className="s-index-i-absolute-rotate-0-scale-100-duration-100-h-12rem-rotate-90-scale-0-transition-all-w-12rem"></i>
            <span className="s-index-span-sr-only">Переключить тему</span>
          </button><button
            id="mobileMenuButton"
            className="s-index-button-pointer-events-none-shrink-0-size-4-bg-accent-border-0-border-input-opacity-50-pointer-events-none-outline-none-ring-2-ring-offset-2-ring-ring-font-medium-gap-2-h-10-bg-accent-text-accent-foreground-inline-flex-items-center-justify-center-hidden-ring-offset-background-rounded-full-text-accent-foreground-text-sm-transition-colors-w-10-whitespace-nowrap">
            <span className="s-index-span-sr-only">Открыть меню</span>
            <i data-lucide="menu" className="s-index-i-h-5-w-5"></i>
          </button></div>
      </div>
      <div className="s-index-div-border-border-border-t-hidden-py-2">
        <div className="s-index-div-container-mx-auto-px-4-py-2">
          <div className="s-index-div-relative-w-full"><i data-lucide="search"
              className=" absolute left-3 top-2.5 h-4 w-4 text-muted-foreground"></i><input type="search"
              className="s-index-input-bg-accent-50-border-0-border-input-cursor-not-allowed-opacity-50-bg-transparent-border-0-font-medium-text-foreground-text-sm-flex-outline-none-ring-1-ring-offset-2-ring-ring-h-10-text-sm-pl-10-text-muted-foreground-px-3-py-2-ring-offset-background-rounded-full-text-base-w-full"
              placeholder="Поиск по имени..."></div>
        </div>
      </div>
    </header>
    <main className="s-index-main-flex-1">
      <section className="s-index-section-bg-muted-py-40-py-32-overflow-hidden-py-24-relative-w-full">
        <div className="s-index-div-absolute-bg-gradient-to-br-from-primary-10-inset-0-to-transparent-via-transparent"></div>
        <div className="s-index-div-container-px-6-mx-auto-px-4-relative">
          <div className="s-index-div-gap-12-grid-items-center-gap-16-grid-cols-2">
            <div className="s-index-div-animate-fade-up-space-y-6">
              <h1 className="s-index-h1-font-medium-text-6xl-text-5xl-text-4xl-tracking-tight">
                Топ 100
                <span className="s-index-span-font-semibold-gradient-text">Инфобизнесменов</span>
                России
              </h1>
              <p className="s-index-p-max-w-600px-text-lg-text-muted-foreground">Рейтинг самых успешных и влиятельных
                предпринимателей в сфере информационного бизнеса</p>
              <div className="s-index-div-flex-flex-col-gap-4-pt-4-flex-row">
                <a className="s-index-a-pointer-events-none-shrink-0-size-4-bg-primary-opacity-50-pointer-events-none-outline-none-ring-2-ring-offset-2-ring-ring-font-medium-gap-2-gradient-glow-h-11-bg-primary-90-inline-flex-items-center-justify-center-px-8-ring-offset-background-rounded-full-text-primary-foreground-text-sm-transition-colors-whitespace-nowrap"
                  href="#professionals">Смотреть рейтинг</a>
                <a className="s-index-a-pointer-events-none-shrink-0-size-4-bg-background-border-border-input-opacity-50-pointer-events-none-outline-none-ring-2-ring-offset-2-ring-ring-font-medium-gap-2-h-11-bg-accent-text-accent-foreground-inline-flex-items-center-justify-center-px-8-ring-offset-background-rounded-full-text-sm-transition-colors-whitespace-nowrap"
                  href="#about">Узнать больше</a>
              </div>
            </div>
            <div className="s-index-div-animate-fade-in-flex-justify-center">
              <div
                className="s-index-div-aspect-4-3-gradient-border-max-w-500px-overflow-hidden-relative-rounded-3xl-shadow-2xl-w-full">
                <div
                  className="s-index-div-absolute-backdrop-blur-sm-bg-gradient-to-br-flex-from-background-20-inset-0-items-center-justify-center-to-muted-20">
                  <div className="s-index-div-gap-6-grid-grid-cols-2-p-8">
                    <div
                      className="s-index-div-backdrop-blur-sm-bg-background-90-border-border-border-50-bg-background-80-flex-flex-col-items-center-p-4-rounded-xl-shadow-lg">
                      <i data-lucide="users" className="s-index-i-h-8-mb-3-text-primary-w-8"></i>
                      <span className="s-index-span-font-medium-text-foreground-text-sm">100+ Профессионалов</span>
                    </div>
                    <div
                      className="s-index-div-backdrop-blur-sm-bg-background-90-border-border-border-50-bg-background-80-flex-flex-col-items-center-p-4-rounded-xl-shadow-lg">
                      <i data-lucide="trending-up" className="s-index-i-h-8-mb-3-text-primary-w-8"></i>
                      <span className="s-index-span-font-medium-text-foreground-text-sm">Актуальный рейтинг</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
      <section id="professionals" className="s-index-section-py-32-py-24-w-full">
        <div className="s-index-div-container-px-6-mx-auto-px-4">
          <div className="s-index-div-flex-flex-col-items-center-justify-center-mb-16-space-y-4-text-center">
            <div className="s-index-div-max-w-3xl-space-y-3">
              <h2 className="s-index-h2-font-medium-text-4xl-text-3xl-tracking-tight">Рейтинг инфобизнесменов</h2>
              <p className="s-index-p-text-lg-text-muted-foreground">Ознакомьтесь с нашим рейтингом самых успешных
                предпринимателей в сфере информационного бизнеса</p>
            </div>
          </div>
          <div id="businessmenContainer"
            className="s-index-div-gap-6-grid-grid-cols-1-grid-cols-3-grid-cols-2-p-4-grid-cols-4">
          </div>
          <div className="s-index-div-flex-justify-center-mt-16"><a
              className="s-index-a-pointer-events-none-shrink-0-size-4-apple-button-apple-button-secondary-bg-background-border-border-input-opacity-50-pointer-events-none-outline-none-ring-2-ring-offset-2-ring-ring-font-medium-gap-2-h-11-bg-accent-text-accent-foreground-inline-flex-items-center-justify-center-px-8-ring-offset-background-rounded-full-text-sm-transition-colors-whitespace-nowrap"
              href="/all-professionals">Показать всех 100 инфобизнесменов</a></div>
        </div>
      </section>
      <section className="s-index-section-py-32-py-24-w-full">
        <div className="s-index-div-container-px-6-mx-auto-px-4">
          <div className="s-index-div-flex-flex-col-items-center-justify-center-mb-16-space-y-4-text-center">
            <div className="s-index-div-max-w-3xl-space-y-3">
              <h2 className="s-index-h2-font-medium-text-4xl-text-3xl-tracking-tight">Лента инфобиза</h2>
              <p className="s-index-p-text-lg-text-muted-foreground">Последние видео от ведущих инфобизнесменов
                России</p>
            </div>
          </div>
          <div id="newsContainer" className="s-index-div-gap-6-grid-grid-cols-1-grid-cols-3-grid-cols-2-p-4-grid-cols-4">
          </div>
          <div className="s-index-div-flex-justify-center-mt-16"><a
              className="s-index-a-pointer-events-none-shrink-0-size-4-apple-button-apple-button-secondary-bg-background-border-border-input-opacity-50-pointer-events-none-outline-none-ring-2-ring-offset-2-ring-ring-font-medium-gap-2-h-11-bg-accent-text-accent-foreground-inline-flex-items-center-justify-center-px-8-ring-offset-background-rounded-full-text-sm-transition-colors-whitespace-nowrap"
              href="/videos">Смотреть все видео</a></div>
        </div>
      </section>
      <section id="about" className="s-index-section-bg-muted-py-32-py-24-w-full">
        <div className="s-index-div-container-px-6-mx-auto-px-4">
          <div className="s-index-div-flex-flex-col-items-center-justify-center-mb-16-space-y-4-text-center">
            <div className="s-index-div-max-w-3xl-space-y-3">
              <h2 className="s-index-h2-font-medium-text-4xl-text-3xl-tracking-tight">О проекте</h2>
              <p className="s-index-p-text-lg-text-muted-foreground">Наш рейтинг составлен на основе объективных показателей
                успешности</p>
            </div>
          </div>
          <div className="s-index-div-gap-12-grid-items-center-gap-16-grid-cols-2-max-w-5xl-mx-auto-py-8">
            <div className="s-index-div-space-y-5">
              <div className="s-index-div-bg-accent-inline-block-px-3-py-1-rounded-full-text-accent-foreground-text-sm">Методология
              </div>
              <h3 className="s-index-h3-font-medium-text-2xl">Как мы составляем рейтинг</h3>
              <p className="s-index-p-leading-relaxed-text-lg-text-muted-foreground">Наш рейтинг основан на комплексной оценке
                различных факторов, включая доход, охват аудитории, влияние в социальных сетях,
                качество контента и отзывы клиентов. Мы регулярно обновляем данные, чтобы предоставить вам самую
                актуальную информацию о лидерах инфобиза.</p>
            </div>
            <div className="s-index-div-space-y-5">
              <div className="s-index-div-bg-accent-inline-block-px-3-py-1-rounded-full-text-accent-foreground-text-sm">Критерии</div>
              <h3 className="s-index-h3-font-medium-text-2xl">Что мы оцениваем</h3>
              <ul className="s-index-ul-gap-3-grid-text-lg-text-muted-foreground">
                <li className="s-index-li-flex-gap-3-items-center">
                  <div className="s-index-div-bg-accent-p-1-rounded-full-text-accent-foreground">
                    <i data-lucide="check" className="s-index-i-h-4-w-4"></i>
                  </div>Годовой доход от информационных продуктов
                </li>
                <li className="s-index-li-flex-gap-3-items-center">
                  <div className="s-index-div-bg-accent-p-1-rounded-full-text-accent-foreground">
                    <i data-lucide="check" className="s-index-i-h-4-w-4"></i>
                  </div>Размер и вовлеченность аудитории
                </li>
                <li className="s-index-li-flex-gap-3-items-center">
                  <div className="s-index-div-bg-accent-p-1-rounded-full-text-accent-foreground">
                    <i data-lucide="check" className="s-index-i-h-4-w-4"></i>
                  </div>Качество и уникальность контента
                </li>
                <li className="s-index-li-flex-gap-3-items-center">
                  <div className="s-index-div-bg-accent-p-1-rounded-full-text-accent-foreground">
                    <i data-lucide="check" className="s-index-i-h-4-w-4"></i>
                  </div>Отзывы и удовлетворенность клиентов
                </li>
              </ul>
            </div>
          </div>
        </div>
      </section>
    </main>
    <footer className="s-index-footer-border-border-border-t-py-12">
      <div className="s-index-div-container-px-6-mx-auto-px-4">
        <div className="s-index-div-flex-flex-col-gap-6-items-center-justify-between-flex-row">
          <div className="s-index-div-flex-gap-2-items-center"><i data-lucide="circle" className="s-index-i-h-5-w-5"></i><span
              className="s-index-span-font-medium-text-lg">Топ 100 Инфобизнесменов</span></div>
          <p className="s-index-p-text-left-text-center-text-muted-foreground-text-sm">2025 Топ 100 Инфобизнесменов. Все права
            защищены.</p>
          <div className="s-index-div-flex-gap-6"><a
              className="s-index-a-font-medium-text-foreground-text-muted-foreground-text-sm-transition-colors"
              href="#">Политика конфиденциальности</a><a
              className="s-index-a-font-medium-text-foreground-text-muted-foreground-text-sm-transition-colors" href="#">Условия
              использования</a></div>
        </div>
      </div>
    </footer>
  </div>
  <div id="mobileMenu" 
    className="s-index-div-backdrop-blur-sm-bg-background-80-duration-300-fixed-inset-0-opacity-0-pointer-events-none-transition-opacity-z-50"
    aria-hidden="true">
    <div className="s-index-div-bottom-0-fixed-inset-x-0-mt-auto-z-50">
      <div className="s-index-div-duration-300-transition-transform-translate-y-full" data-state="closed">
        <div className="s-index-div-bg-background-border-border-border-t-flex-flex-col-h-calc-100vh-4rem-relative-rounded-t-125rem">
          <div className="s-index-div-bg-muted-h-2-mt-4-mx-auto-rounded-full-w-100px"></div>
          <div className="s-index-div-p-6-pt-8">
            <nav className="s-index-nav-gap-6-grid">
              <a className="s-index-a-flex-font-medium-gap-2-group-bg-accent-text-accent-foreground-items-center-px-3-py-2-rounded-md-text-base-transition-colors-w-full" href="/">
                <i data-lucide="home" className="s-index-i-h-5-w-5"></i>
                Главная
              </a>
              <a className="s-index-a-flex-font-medium-gap-2-group-bg-accent-text-accent-foreground-items-center-px-3-py-2-rounded-md-text-base-transition-colors-w-full" href="#professionals">
                <i data-lucide="users" className="s-index-i-h-5-w-5"></i>
                Рейтинг
              </a>
              <a className="s-index-a-flex-font-medium-gap-2-group-bg-accent-text-accent-foreground-items-center-px-3-py-2-rounded-md-text-base-transition-colors-w-full" href="/videos">
                <i data-lucide="play" className="s-index-i-h-5-w-5"></i>
                Лента
              </a>
              <a className="s-index-a-flex-font-medium-gap-2-group-bg-accent-text-accent-foreground-items-center-px-3-py-2-rounded-md-text-base-transition-colors-w-full" href="#about">
                <i data-lucide="info" className="s-index-i-h-5-w-5"></i>
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
        <div className="s-index-div-bg-background-border-border-border-duration-100-ease-in-out-group-translate-y-1-shadow-xl-overflow-hidden-rounded-xl-shadow-sm-text-foreground-transform-transition-all">
          <div className="s-index-div-h-56-overflow-hidden-relative-rounded-t-xl-w-full">
            <div className="s-index-div-absolute-left-4-top-4-z-10">
              <div className="s-index-div-backdrop-blur-sm-bg-background-90-bg-background-font-medium-px-3-py-1-rounded-full-text-foreground-text-xs-transition-colors">
                #${data.rank}
              </div>
            </div>
            <img alt="${data.name}" 
              src="${data.image}" 
              className="s-index-img-duration-500-scale-105-h-full-object-cover-transition-transform-w-full"
            />
          </div>
          <div className="s-index-div-p-4">
            <h3 className="s-index-h3-font-medium-text-foreground-text-xl">${data.name}</h3>
            <p className="s-index-p-mt-1-text-muted-foreground-text-sm">${data.category}</p>
            <div className="s-index-div-gap-4-grid-grid-cols-2-mt-5">
              <div>
                <p className="s-index-p-text-muted-foreground-text-xs-tracking-wide-uppercase">Годовой доход</p>
                <p className="s-index-p-font-medium-mt-1-text-foreground">${data.revenue}</p>
              </div>
              <div>
                <p className="s-index-p-text-muted-foreground-text-xs-tracking-wide-uppercase">Аудитория</p>
                <p className="s-index-p-font-medium-mt-1-text-foreground">${data.subscribers}</p>
              </div>
            </div>
            <div className="s-index-div-flex-flex-wrap-gap-2-mt-5">
              ${data.tags.map(tag => `
                <span className="s-index-span-bg-accent-font-medium-bg-accent-80-inline-flex-items-center-px-25-py-05-rounded-full-text-accent-foreground-text-xs-transition-colors">
                  ${tag}
                </span>
              `).join('')}
            </div>
          </div>
          <div className="s-index-div-flex-items-center-justify-between-p-4-pt-0">
            <div className="s-index-div-flex-gap-2">
              ${Object.entries(data.socialLinks).map(([platform, url]) => `
                <a href="${url}" 
                  target="_blank" 
                  rel="noopener noreferrer" 
                  className="s-index-a-bg-accent-h-9-bg-accent-80-inline-flex-items-center-justify-center-rounded-full-text-accent-foreground-transition-colors-w-9"
                >
                  <span className="s-index-span-sr-only">${platform}</span>
                  ${getSocialIcon(platform)}
                </a>
              `).join('')}
            </div>
            <a href="${data.profileUrl}" 
              className="s-index-a-border-border-input-font-medium-h-9-bg-accent-text-accent-foreground-inline-flex-items-center-justify-center-px-4-rounded-full-text-sm-transition-colors"
            >
              Подробнее
            </a>
          </div>
        </div>
      `],
      ['news', (data) => `
        <div className="s-index-div-bg-background-border-border-border-duration-100-ease-in-out-translate-y-1-shadow-xl-overflow-hidden-rounded-lg-shadow-sm-text-foreground-transform-transition-all">
          <div className="s-index-div-aspect-video-group-overflow-hidden-relative-rounded-t-2xl-w-full">
            <div className="s-index-div-absolute-bg-foreground-5-bg-foreground-10-inset-0-transition-colors-z-10"></div>
            <div className="s-index-div-absolute-duration-100-flex-opacity-100-inset-0-items-center-justify-center-opacity-0-transition-opacity-z-20">
              <button className="s-index-button-pointer-events-none-shrink-0-size-4-backdrop-blur-sm-bg-background-95-bg-background-95-bg-primary-text-primary-foreground-text-primary-opacity-50-pointer-events-none-duration-100-outline-none-ring-2-ring-offset-2-ring-ring-font-medium-gap-2-h-14-bg-primary-scale-105-text-primary-foreground-inline-flex-items-center-justify-center-ring-offset-background-rounded-full-text-primary-text-sm-transition-all-w-14-whitespace-nowrap">
                <i data-lucide="play" className="s-index-i-h-6-w-6"></i>
                <span className="s-index-span-sr-only">Смотреть видео</span>
              </button>
            </div>
            <div className="s-index-div-absolute-backdrop-blur-sm-bg-background-95-bottom-3-text-foreground-font-medium-px-2-py-1-right-3-rounded-md-text-foreground-text-xs-z-20">
              ${data.duration || '15:18'}
            </div>
            <div className="s-index-div-absolute-left-3-top-3-z-20">
              <div className="s-index-div-backdrop-blur-sm-bg-primary-10-bg-primary-20-bg-primary-text-primary-foreground-text-primary-foreground-duration-100-font-medium-gap-1-bg-primary-text-primary-foreground-inline-flex-items-center-px-2-py-1-rounded-full-text-primary-text-xs-transition-colors">
                <i data-lucide="youtube" className="s-index-i-h-3-w-3"></i>
                <span>${data.platform || 'YouTube'}</span>
              </div>
            </div>
            <img 
              alt="${data.title}" 
              loading="lazy"
              decoding="async"
              className="s-index-img-absolute-duration-500-scale-105-h-full-inset-0-object-cover-transition-transform-w-full"
              src="${data.image}"
            >
          </div>
          <div className="s-index-div-p-5">
            <a className="s-index-a-block-group-title" href="${data.url || '#'}">
              <h3 className="s-index-h3-font-medium-text-primary-line-clamp-2-text-foreground-text-lg-transition-colors">
                ${data.title}
              </h3>
            </a>
            <div className="s-index-div-flex-items-center-justify-between-mt-4">
              <div className="s-index-div-flex-gap-2-items-center">
                <span className="s-index-span-flex-h-8-overflow-hidden-relative-ring-1-ring-border-rounded-full-shrink-0-w-8">
                  <img 
                    className="s-index-img-aspect-square-h-full-w-full" 
                    alt="${data.author}"
                    src="${data.authorImage || 'https://v0-topchik-infobiza.vercel.app/placeholder.svg?height=100&width=100'}"
                  >
                </span>
                <a className="s-index-a-font-medium-text-primary-text-muted-foreground-text-sm-transition-colors" href="${data.authorUrl || '#'}">
                  ${data.author}
                </a>
              </div>
            </div>
          </div>
          <div className="s-index-div-border-border-border-t-flex-items-center-px-5-py-3-text-muted-foreground-text-xs">
            <div className="s-index-div-flex-gap-2-items-center">
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

      return `<i data-lucide="${iconMap[platform]}" className="s-index-i-h-4-w-4"></i>`;
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