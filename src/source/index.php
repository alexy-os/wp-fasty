<!DOCTYPE html>
<html lang="ru" className="dark">

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
      document.documentElement.classList.add('dark');
      document.documentElement.style.colorScheme = 'dark';
    } else {
      document.documentElement.classList.remove('dark');
      document.documentElement.style.colorScheme = 'light';
    }
    
    function toggleDarkMode() {
      const isDark = document.documentElement.classList.toggle('dark');
      localStorage.setItem('darkMode', isDark);
      document.documentElement.style.colorScheme = isDark ? 'dark' : 'light';
    }
  </script>
</head>

<body className="font-base bg-background text-foreground min-h-screen">
  <div className="flex flex-col min-h-screen">
    <header className="sticky top-0 z-50 backdrop-blur-sm border-b border-border/50">
      <div className="container mx-auto px-4 md:px-6 flex h-16 items-center justify-between">
        <a className="flex items-center gap-2 text-lg font-medium" href="/">
          <i data-lucide="award" className="h-5 w-5"></i>
          <span>Топ 100 Инфобизнесменов</span>
        </a>
        <div className="hidden md:flex flex-1 max-w-md mx-4">
          <div className="relative w-full">
            <i data-lucide="search" className="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground"></i>
            <input type="search"
              className="flex border-input px-3 py-2 text-base ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm w-full bg-accent/50 border-0 rounded-full pl-10 h-10 focus-visible:ring-1 focus-visible:ring-ring"
              placeholder="Поиск по имени...">
          </div>
        </div>
        <nav className="hidden md:flex gap-8"><a className="text-sm font-medium hover:text-muted-foreground transition-colors"
            href="/">Главная</a><a className="text-sm font-medium hover:text-muted-foreground transition-colors"
            href="#professionals">Рейтинг</a><a
            className="text-sm font-medium hover:text-muted-foreground transition-colors" href="/videos">Лента</a><a
            className="text-sm font-medium hover:text-muted-foreground transition-colors" href="#about">О проекте</a></nav>
        <div className="flex items-center gap-4"><button onclick="toggleDarkMode()"
            className="inline-flex items-center justify-center relative w-10 h-10 rounded-full bg-accent text-accent-foreground transition-colors hover:bg-accent/80">
            <i data-lucide="sun"
              className="h-[1.2rem] w-[1.2rem] absolute transition-all duration-100 rotate-0 scale-100 dark:rotate-90 dark:scale-0"></i>
            <i data-lucide="moon"
              className="h-[1.2rem] w-[1.2rem] absolute transition-all duration-100 rotate-90 scale-0 dark:rotate-0 dark:scale-100"></i>
            <span className="sr-only">Переключить тему</span>
          </button><button
            id="mobileMenuButton"
            className="inline-flex items-center justify-center gap-2 whitespace-nowrap text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 border-input hover:bg-accent hover:text-accent-foreground md:hidden rounded-full w-10 h-10 bg-accent text-accent-foreground border-0">
            <span className="sr-only">Открыть меню</span>
            <i data-lucide="menu" className="h-5 w-5"></i>
          </button></div>
      </div>
      <div className="md:hidden border-t border-border py-2">
        <div className="container mx-auto px-4 py-2">
          <div className="relative w-full"><i data-lucide="search"
              className=" absolute left-3 top-2.5 h-4 w-4 text-muted-foreground"></i><input type="search"
              className="flex border-input px-3 py-2 text-base ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm w-full bg-accent/50 border-0 rounded-full pl-10 h-10 focus-visible:ring-1 focus-visible:ring-ring"
              placeholder="Поиск по имени..."></div>
        </div>
      </div>
    </header>
    <main className="flex-1">
      <section className="w-full py-24 md:py-32 lg:py-40 bg-muted relative overflow-hidden">
        <div className="absolute inset-0 bg-gradient-to-br from-primary/10 via-transparent to-transparent"></div>
        <div className="container mx-auto px-4 md:px-6 relative">
          <div className="grid gap-12 lg:grid-cols-2 lg:gap-16 items-center">
            <div className="space-y-6 animate-fade-up">
              <h1 className="text-4xl md:text-5xl lg:text-6xl font-medium tracking-tight">
                Топ 100
                <span className="gradient-text font-semibold">Инфобизнесменов</span>
                России
              </h1>
              <p className="text-lg text-muted-foreground max-w-[600px]">Рейтинг самых успешных и влиятельных
                предпринимателей в сфере информационного бизнеса</p>
              <div className="flex flex-col sm:flex-row gap-4 pt-4">
                <a className="gradient-glow inline-flex items-center justify-center gap-2 whitespace-nowrap text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 bg-primary text-primary-foreground hover:bg-primary/90 h-11 rounded-full px-8"
                  href="#professionals">Смотреть рейтинг</a>
                <a className="inline-flex items-center justify-center gap-2 whitespace-nowrap text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-11 rounded-full px-8"
                  href="#about">Узнать больше</a>
              </div>
            </div>
            <div className="flex justify-center animate-fade-in">
              <div
                className="relative w-full max-w-[500px] aspect-[4/3] rounded-3xl overflow-hidden shadow-2xl gradient-border">
                <div
                  className="absolute inset-0 bg-gradient-to-br from-background/20 to-muted/20 backdrop-blur-sm flex items-center justify-center">
                  <div className="grid grid-cols-2 gap-6 p-8">
                    <div
                      className="bg-background/90 dark:bg-background/80 p-4 rounded-xl shadow-lg flex flex-col items-center backdrop-blur-sm border border-border/50">
                      <i data-lucide="users" className="h-8 w-8 text-primary mb-3"></i>
                      <span className="text-sm font-medium text-foreground">100+ Профессионалов</span>
                    </div>
                    <div
                      className="bg-background/90 dark:bg-background/80 p-4 rounded-xl shadow-lg flex flex-col items-center backdrop-blur-sm border border-border/50">
                      <i data-lucide="trending-up" className="h-8 w-8 text-primary mb-3"></i>
                      <span className="text-sm font-medium text-foreground">Актуальный рейтинг</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
      <section id="professionals" className="w-full py-24 md:py-32">
        <div className="container mx-auto px-4 md:px-6">
          <div className="flex flex-col items-center justify-center space-y-4 text-center mb-16">
            <div className="space-y-3 max-w-3xl">
              <h2 className="text-3xl md:text-4xl font-medium tracking-tight">Рейтинг инфобизнесменов</h2>
              <p className="text-lg text-muted-foreground">Ознакомьтесь с нашим рейтингом самых успешных
                предпринимателей в сфере информационного бизнеса</p>
            </div>
          </div>
          <div id="businessmenContainer"
            className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 p-4">
          </div>
          <div className="flex justify-center mt-16"><a
              className="inline-flex items-center justify-center gap-2 whitespace-nowrap text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&amp;_svg]:pointer-events-none [&amp;_svg]:size-4 [&amp;_svg]:shrink-0 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-11 rounded-full px-8 apple-button apple-button-secondary"
              href="/all-professionals">Показать всех 100 инфобизнесменов</a></div>
        </div>
      </section>
      <section className="w-full py-24 md:py-32">
        <div className="container mx-auto px-4 md:px-6">
          <div className="flex flex-col items-center justify-center space-y-4 text-center mb-16">
            <div className="space-y-3 max-w-3xl">
              <h2 className="text-3xl md:text-4xl font-medium tracking-tight">Лента инфобиза</h2>
              <p className="text-lg text-muted-foreground">Последние видео от ведущих инфобизнесменов
                России</p>
            </div>
          </div>
          <div id="newsContainer" className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 p-4">
          </div>
          <div className="flex justify-center mt-16"><a
              className="inline-flex items-center justify-center gap-2 whitespace-nowrap text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&amp;_svg]:pointer-events-none [&amp;_svg]:size-4 [&amp;_svg]:shrink-0 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-11 rounded-full px-8 apple-button apple-button-secondary"
              href="/videos">Смотреть все видео</a></div>
        </div>
      </section>
      <section id="about" className="w-full py-24 md:py-32 bg-muted">
        <div className="container mx-auto px-4 md:px-6">
          <div className="flex flex-col items-center justify-center space-y-4 text-center mb-16">
            <div className="space-y-3 max-w-3xl">
              <h2 className="text-3xl md:text-4xl font-medium tracking-tight">О проекте</h2>
              <p className="text-lg text-muted-foreground">Наш рейтинг составлен на основе объективных показателей
                успешности</p>
            </div>
          </div>
          <div className="mx-auto grid max-w-5xl items-center gap-12 py-8 lg:grid-cols-2 lg:gap-16">
            <div className="space-y-5">
              <div className="inline-block rounded-full bg-accent text-accent-foreground px-3 py-1 text-sm">Методология
              </div>
              <h3 className="text-2xl font-medium">Как мы составляем рейтинг</h3>
              <p className="text-muted-foreground text-lg leading-relaxed">Наш рейтинг основан на комплексной оценке
                различных факторов, включая доход, охват аудитории, влияние в социальных сетях,
                качество контента и отзывы клиентов. Мы регулярно обновляем данные, чтобы предоставить вам самую
                актуальную информацию о лидерах инфобиза.</p>
            </div>
            <div className="space-y-5">
              <div className="inline-block rounded-full bg-accent text-accent-foreground px-3 py-1 text-sm">Критерии</div>
              <h3 className="text-2xl font-medium">Что мы оцениваем</h3>
              <ul className="grid gap-3 text-muted-foreground text-lg">
                <li className="flex items-center gap-3">
                  <div className="rounded-full bg-accent text-accent-foreground p-1">
                    <i data-lucide="check" className="h-4 w-4"></i>
                  </div>Годовой доход от информационных продуктов
                </li>
                <li className="flex items-center gap-3">
                  <div className="rounded-full bg-accent text-accent-foreground p-1">
                    <i data-lucide="check" className="h-4 w-4"></i>
                  </div>Размер и вовлеченность аудитории
                </li>
                <li className="flex items-center gap-3">
                  <div className="rounded-full bg-accent text-accent-foreground p-1">
                    <i data-lucide="check" className="h-4 w-4"></i>
                  </div>Качество и уникальность контента
                </li>
                <li className="flex items-center gap-3">
                  <div className="rounded-full bg-accent text-accent-foreground p-1">
                    <i data-lucide="check" className="h-4 w-4"></i>
                  </div>Отзывы и удовлетворенность клиентов
                </li>
              </ul>
            </div>
          </div>
        </div>
      </section>
    </main>
    <footer className="border-t border-border py-12">
      <div className="container mx-auto px-4 md:px-6">
        <div className="flex flex-col md:flex-row items-center justify-between gap-6">
          <div className="flex items-center gap-2"><i data-lucide="circle" className="h-5 w-5"></i><span
              className="text-lg font-medium">Топ 100 Инфобизнесменов</span></div>
          <p className="text-center text-sm text-muted-foreground md:text-left">2025 Топ 100 Инфобизнесменов. Все права
            защищены.</p>
          <div className="flex gap-6"><a
              className="text-sm font-medium text-muted-foreground hover:text-foreground transition-colors"
              href="#">Политика конфиденциальности</a><a
              className="text-sm font-medium text-muted-foreground hover:text-foreground transition-colors" href="#">Условия
              использования</a></div>
        </div>
      </div>
    </footer>
  </div>
  <div id="mobileMenu" 
    className="fixed inset-0 z-50 bg-background/80 backdrop-blur-sm opacity-0 pointer-events-none transition-opacity duration-300"
    aria-hidden="true">
    <div className="fixed inset-x-0 bottom-0 z-50 mt-auto">
      <div className="translate-y-full transition-transform duration-300" data-state="closed">
        <div className="relative h-[calc(100vh-4rem)] bg-background border-t border-border rounded-t-[1.25rem] flex flex-col">
          <div className="mx-auto mt-4 h-2 w-[100px] rounded-full bg-muted"></div>
          <div className="p-6 pt-8">
            <nav className="grid gap-6">
              <a className="group flex w-full items-center gap-2 rounded-md px-3 py-2 text-base font-medium hover:bg-accent hover:text-accent-foreground transition-colors" href="/">
                <i data-lucide="home" className="h-5 w-5"></i>
                Главная
              </a>
              <a className="group flex w-full items-center gap-2 rounded-md px-3 py-2 text-base font-medium hover:bg-accent hover:text-accent-foreground transition-colors" href="#professionals">
                <i data-lucide="users" className="h-5 w-5"></i>
                Рейтинг
              </a>
              <a className="group flex w-full items-center gap-2 rounded-md px-3 py-2 text-base font-medium hover:bg-accent hover:text-accent-foreground transition-colors" href="/videos">
                <i data-lucide="play" className="h-5 w-5"></i>
                Лента
              </a>
              <a className="group flex w-full items-center gap-2 rounded-md px-3 py-2 text-base font-medium hover:bg-accent hover:text-accent-foreground transition-colors" href="#about">
                <i data-lucide="info" className="h-5 w-5"></i>
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
        <div className="group rounded-xl border border-border bg-background text-foreground shadow-sm hover:shadow-xl transition-all duration-100 ease-in-out transform hover:-translate-y-1 overflow-hidden">
          <div className="relative h-56 w-full overflow-hidden rounded-t-xl">
            <div className="absolute top-4 left-4 z-10">
              <div className="bg-background/90 dark:bg-background text-foreground px-3 py-1 rounded-full text-xs font-medium backdrop-blur-sm transition-colors">
                #${data.rank}
              </div>
            </div>
            <img alt="${data.name}" 
              src="${data.image}" 
              className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
            />
          </div>
          <div className="p-4">
            <h3 className="text-xl font-medium text-foreground">${data.name}</h3>
            <p className="mt-1 text-sm text-muted-foreground">${data.category}</p>
            <div className="mt-5 grid grid-cols-2 gap-4">
              <div>
                <p className="text-xs text-muted-foreground uppercase tracking-wide">Годовой доход</p>
                <p className="mt-1 font-medium text-foreground">${data.revenue}</p>
              </div>
              <div>
                <p className="text-xs text-muted-foreground uppercase tracking-wide">Аудитория</p>
                <p className="mt-1 font-medium text-foreground">${data.subscribers}</p>
              </div>
            </div>
            <div className="mt-5 flex flex-wrap gap-2">
              ${data.tags.map(tag => `
                <span className="inline-flex items-center rounded-full bg-accent px-2.5 py-0.5 text-xs font-medium text-accent-foreground transition-colors hover:bg-accent/80">
                  ${tag}
                </span>
              `).join('')}
            </div>
          </div>
          <div className="flex items-center justify-between p-4 pt-0">
            <div className="flex gap-2">
              ${Object.entries(data.socialLinks).map(([platform, url]) => `
                <a href="${url}" 
                  target="_blank" 
                  rel="noopener noreferrer" 
                  className="inline-flex h-9 w-9 items-center justify-center rounded-full bg-accent text-accent-foreground transition-colors hover:bg-accent/80"
                >
                  <span className="sr-only">${platform}</span>
                  ${getSocialIcon(platform)}
                </a>
              `).join('')}
            </div>
            <a href="${data.profileUrl}" 
              className="inline-flex items-center justify-center rounded-full border border-input px-4 h-9 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground"
            >
              Подробнее
            </a>
          </div>
        </div>
      `],
      ['news', (data) => `
        <div className="rounded-lg border border-border bg-background text-foreground shadow-sm hover:shadow-xl transition-all duration-100 ease-in-out transform hover:-translate-y-1 overflow-hidden">
          <div className="relative aspect-video w-full overflow-hidden rounded-t-2xl group">
            <div className="absolute inset-0 bg-foreground/5 group-hover:bg-foreground/10 transition-colors z-10"></div>
            <div className="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-100 z-20">
              <button className="inline-flex items-center justify-center gap-2 whitespace-nowrap text-sm font-medium ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 w-14 h-14 rounded-full 
                bg-background/95 text-primary hover:bg-primary hover:text-primary-foreground
                dark:bg-background/95 dark:text-primary dark:hover:bg-primary dark:hover:text-primary-foreground
                backdrop-blur-sm hover:scale-105 transition-all duration-100">
                <i data-lucide="play" className="h-6 w-6"></i>
                <span className="sr-only">Смотреть видео</span>
              </button>
            </div>
            <div className="absolute bottom-3 right-3 bg-background/95 text-foreground dark:text-foreground backdrop-blur-sm text-xs font-medium px-2 py-1 rounded-md z-20">
              ${data.duration || '15:18'}
            </div>
            <div className="absolute top-3 left-3 z-20">
              <div className="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full
                bg-primary/10 text-primary hover:bg-primary hover:text-primary-foreground
                dark:bg-primary/20 dark:text-primary-foreground dark:hover:bg-primary dark:hover:text-primary-foreground
                backdrop-blur-sm transition-colors duration-100">
                <i data-lucide="youtube" className="h-3 w-3"></i>
                <span>${data.platform || 'YouTube'}</span>
              </div>
            </div>
            <img 
              alt="${data.title}" 
              loading="lazy"
              decoding="async"
              className="absolute h-full w-full inset-0 object-cover transition-transform duration-500 group-hover:scale-105"
              src="${data.image}"
            >
          </div>
          <div className="p-5">
            <a className="block group/title" href="${data.url || '#'}">
              <h3 className="font-medium text-lg line-clamp-2 text-foreground group-hover/title:text-primary transition-colors">
                ${data.title}
              </h3>
            </a>
            <div className="flex items-center justify-between mt-4">
              <div className="flex items-center gap-2">
                <span className="relative flex shrink-0 overflow-hidden rounded-full h-8 w-8 ring-1 ring-border">
                  <img 
                    className="aspect-square h-full w-full" 
                    alt="${data.author}"
                    src="${data.authorImage || 'https://v0-topchik-infobiza.vercel.app/placeholder.svg?height=100&width=100'}"
                  >
                </span>
                <a className="text-sm font-medium text-muted-foreground hover:text-primary transition-colors" href="${data.authorUrl || '#'}">
                  ${data.author}
                </a>
              </div>
            </div>
          </div>
          <div className="flex items-center px-5 py-3 border-t border-border text-xs text-muted-foreground">
            <div className="flex items-center gap-2">
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

      return `<i data-lucide="${iconMap[platform]}" className="h-4 w-4"></i>`;
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