<!DOCTYPE html>
<html lang="ru" class="wdaaa">

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

<body class="szo0y">
  <div class="dcllc">
    <header class="xoyrn">
      <div class="qzmjn">
        <a class="kolpe" href="/">
          <i data-lucide="award" class="xbhad"></i>
          <span>Топ 100 Инфобизнесменов</span>
        </a>
        <div class="zhjm0">
          <div class="lwoda">
            <i data-lucide="search" class="kmayp"></i>
            <input type="search"
              class="otgiy"
              placeholder="Поиск по имени...">
          </div>
        </div>
        <nav class="zlm5c"><a class="z5wia hhz4m"
            href="/">Главная</a><a class="z5wia hhz4m"
            href="#professionals">Рейтинг</a><a
            class="z5wia hhz4m" href="/videos">Лента</a><a
            class="z5wia hhz4m" href="#about">О проекте</a></nav>
        <div class="d55z2"><button onclick="toggleDarkMode()"
            class="cytwc hhz4m">
            <i data-lucide="sun"
              class="ox5ar"></i>
            <i data-lucide="moon"
              class="aiosr"></i>
            <span class="ksdaa">Переключить тему</span>
          </button><button
            id="mobileMenuButton"
            class="cytwc z5wia defuo hhz4m">
            <span class="ksdaa">Открыть меню</span>
            <i data-lucide="menu" class="xbhad"></i>
          </button></div>
      </div>
      <div class="unfoq">
        <div class="bbshj">
          <div class="lwoda"><i data-lucide="search"
              class=" absolute left-3 top-2.5 h-4 w-4 text-muted-foreground"></i><input type="search"
              class="otgiy"
              placeholder="Поиск по имени..."></div>
        </div>
      </div>
    </header>
    <main class="nedaa">
      <section class="oq45n">
        <div class="bvaeg"></div>
        <div class="dvq2n">
          <div class="luu5j">
            <div class="wdilk">
              <h1 class="mipdb">
                Топ 100
                <span class="gtgad">Инфобизнесменов</span>
                России
              </h1>
              <p class="f0vbk">Рейтинг самых успешных и влиятельных
                предпринимателей в сфере информационного бизнеса</p>
              <div class="gxuos">
                <a class="cytwc z5wia hhz4m"
                  href="#professionals">Смотреть рейтинг</a>
                <a class="cytwc z5wia defuo ww2r5 hhz4m"
                  href="#about">Узнать больше</a>
              </div>
            </div>
            <div class="f0rwk">
              <div
                class="pvwjw">
                <div
                  class="oip0h">
                  <div class="a1cud">
                    <div
                      class="lnubu">
                      <i data-lucide="users" class="jv3gs"></i>
                      <span class="z5wia hhz4m">100+ Профессионалов</span>
                    </div>
                    <div
                      class="lnubu">
                      <i data-lucide="trending-up" class="jv3gs"></i>
                      <span class="z5wia hhz4m">Актуальный рейтинг</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
      <section id="professionals" class="a3v33">
        <div class="x0khv">
          <div class="valvo">
            <div class="io0tv">
              <h2 class="wj1yt">Рейтинг инфобизнесменов</h2>
              <p class="d25kj">Ознакомьтесь с нашим рейтингом самых успешных
                предпринимателей в сфере информационного бизнеса</p>
            </div>
          </div>
          <div id="businessmenContainer"
            class="ixmdj">
          </div>
          <div class="pod42"><a
              class="cytwc z5wia defuo ww2r5 hhz4m"
              href="/all-professionals">Показать всех 100 инфобизнесменов</a></div>
        </div>
      </section>
      <section class="a3v33">
        <div class="x0khv">
          <div class="valvo">
            <div class="io0tv">
              <h2 class="wj1yt">Лента инфобиза</h2>
              <p class="d25kj">Последние видео от ведущих инфобизнесменов
                России</p>
            </div>
          </div>
          <div id="newsContainer" class="ixmdj">
          </div>
          <div class="pod42"><a
              class="cytwc z5wia defuo ww2r5 hhz4m"
              href="/videos">Смотреть все видео</a></div>
        </div>
      </section>
      <section id="about" class="zcexf">
        <div class="x0khv">
          <div class="valvo">
            <div class="io0tv">
              <h2 class="wj1yt">О проекте</h2>
              <p class="d25kj">Наш рейтинг составлен на основе объективных показателей
                успешности</p>
            </div>
          </div>
          <div class="gmbf0">
            <div class="xupda">
              <div class="oneuv">Методология
              </div>
              <h3 class="lkbad">Как мы составляем рейтинг</h3>
              <p class="lo33d">Наш рейтинг основан на комплексной оценке
                различных факторов, включая доход, охват аудитории, влияние в социальных сетях,
                качество контента и отзывы клиентов. Мы регулярно обновляем данные, чтобы предоставить вам самую
                актуальную информацию о лидерах инфобиза.</p>
            </div>
            <div class="xupda">
              <div class="oneuv">Критерии</div>
              <h3 class="lkbad">Что мы оцениваем</h3>
              <ul class="kza52">
                <li class="eb5z2">
                  <div class="rkc1k">
                    <i data-lucide="check" class="xdgad"></i>
                  </div>Годовой доход от информационных продуктов
                </li>
                <li class="eb5z2">
                  <div class="rkc1k">
                    <i data-lucide="check" class="xdgad"></i>
                  </div>Размер и вовлеченность аудитории
                </li>
                <li class="eb5z2">
                  <div class="rkc1k">
                    <i data-lucide="check" class="xdgad"></i>
                  </div>Качество и уникальность контента
                </li>
                <li class="eb5z2">
                  <div class="rkc1k">
                    <i data-lucide="check" class="xdgad"></i>
                  </div>Отзывы и удовлетворенность клиентов
                </li>
              </ul>
            </div>
          </div>
        </div>
      </section>
    </main>
    <footer class="drrdt">
      <div class="x0khv">
        <div class="oohxc">
          <div class="fd4z2"><i data-lucide="circle" class="xbhad"></i><span
              class="rlbad">Топ 100 Инфобизнесменов</span></div>
          <p class="hrzmc">2025 Топ 100 Инфобизнесменов. Все права
            защищены.</p>
          <div class="z2cda"><a
              class="z5wia hhz4m"
              href="#">Политика конфиденциальности</a><a
              class="z5wia hhz4m" href="#">Условия
              использования</a></div>
        </div>
      </div>
    </footer>
  </div>
  <div id="mobileMenu" 
    class="vnpuy"
    aria-hidden="true">
    <div class="awrkl">
      <div class="ci24y" data-state="closed">
        <div class="vagfv">
          <div class="egnl2"></div>
          <div class="cqqhd">
            <nav class="kmcda">
              <a class="defuo hhz4m" href="/">
                <i data-lucide="home" class="xbhad"></i>
                Главная
              </a>
              <a class="defuo hhz4m" href="#professionals">
                <i data-lucide="users" class="xbhad"></i>
                Рейтинг
              </a>
              <a class="defuo hhz4m" href="/videos">
                <i data-lucide="play" class="xbhad"></i>
                Лента
              </a>
              <a class="defuo hhz4m" href="#about">
                <i data-lucide="info" class="xbhad"></i>
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
        <div class="et0wm">
          <div class="dl2bt">
            <div class="spvlw">
              <div class="rd1ym">
                #${data.rank}
              </div>
            </div>
            <img alt="${data.name}" 
              src="${data.image}" 
              class="mj5do"
            />
          </div>
          <div class="oodaa">
            <h3 class="jszw1">${data.name}</h3>
            <p class="yudl2">${data.category}</p>
            <div class="gn5s5">
              <div>
                <p class="hdvrl">Годовой доход</p>
                <p class="r5w41">${data.revenue}</p>
              </div>
              <div>
                <p class="hdvrl">Аудитория</p>
                <p class="r5w41">${data.subscribers}</p>
              </div>
            </div>
            <div class="cddtx">
              ${data.tags.map(tag => `
                <span class="qusoe">
                  ${tag}
                </span>
              `).join('')}
            </div>
          </div>
          <div class="ecc03">
            <div class="v2cda">
              ${Object.entries(data.socialLinks).map(([platform, url]) => `
                <a href="${url}" 
                  target="_blank" 
                  rel="noopener noreferrer" 
                  class="cytwc hhz4m"
                >
                  <span class="ksdaa">${platform}</span>
                  ${getSocialIcon(platform)}
                </a>
              `).join('')}
            </div>
            <a href="${data.profileUrl}" 
              class="cytwc ufufg defuo ww2r5 hhz4m"
            >
              Подробнее
            </a>
          </div>
        </div>
      `],
      ['news', (data) => `
        <div class="flfgq">
          <div class="qc5mm">
            <div class="ic3aa"></div>
            <div class="q5rnb">
              <button class="cytwc z5wia hhz4m">
                <i data-lucide="play" class="x5had"></i>
                <span class="ksdaa">Смотреть видео</span>
              </button>
            </div>
            <div class="baks2">
              ${data.duration || '15:18'}
            </div>
            <div class="rj2hx">
              <div class="yg4wx">
                <i data-lucide="youtube" class="xffad"></i>
                <span>${data.platform || 'YouTube'}</span>
              </div>
            </div>
            <img 
              alt="${data.title}" 
              loading="lazy"
              decoding="async"
              class="qoe2s"
              src="${data.image}"
            >
          </div>
          <div class="podaa">
            <a class="vcdaa" href="${data.url || '#'}">
              <h3 class="pdxj1">
                ${data.title}
              </h3>
            </a>
            <div class="t1rfx">
              <div class="fd4z2">
                <span class="sl1ss">
                  <img 
                    class="xecj5" 
                    alt="${data.author}"
                    src="${data.authorImage || 'https://v0-topchik-infobiza.vercel.app/placeholder.svg?height=100&width=100'}"
                  >
                </span>
                <a class="z5wia hhz4m" href="${data.authorUrl || '#'}">
                  ${data.author}
                </a>
              </div>
            </div>
          </div>
          <div class="j1hfq">
            <div class="fd4z2">
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

      return `<i data-lucide="${iconMap[platform]}" class="xdgad"></i>`;
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