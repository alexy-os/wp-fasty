<!DOCTYPE html>
<html lang="ru" className="wdaaa">

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

<body className="szo0y">
  <div className="dcllc">
    <header className="xoyrn">
      <div className="qzmjn">
        <a className="kolpe" href="/">
          <i data-lucide="award" className="xbhad"></i>
          <span>Топ 100 Инфобизнесменов</span>
        </a>
        <div className="zhjm0">
          <div className="lwoda">
            <i data-lucide="search" className="kmayp"></i>
            <input type="search"
              className="otgiy"
              placeholder="Поиск по имени...">
          </div>
        </div>
        <nav className="zlm5c"><a className="z5wia l0lfi"
            href="/">Главная</a><a className="z5wia l0lfi"
            href="#professionals">Рейтинг</a><a
            className="z5wia l0lfi" href="/videos">Лента</a><a
            className="z5wia l0lfi" href="#about">О проекте</a></nav>
        <div className="d55z2"><button onclick="toggleDarkMode()"
            className="cytwc yu4ca">
            <i data-lucide="sun"
              className="ox5ar"></i>
            <i data-lucide="moon"
              className="aiosr"></i>
            <span className="ksdaa">Переключить тему</span>
          </button><button
            id="mobileMenuButton"
            className="cytwc z5wia defuo yu4ca">
            <span className="ksdaa">Открыть меню</span>
            <i data-lucide="menu" className="xbhad"></i>
          </button></div>
      </div>
      <div className="unfoq">
        <div className="bbshj">
          <div className="lwoda"><i data-lucide="search"
              className=" absolute left-3 top-2.5 h-4 w-4 text-muted-foreground"></i><input type="search"
              className="otgiy"
              placeholder="Поиск по имени..."></div>
        </div>
      </div>
    </header>
    <main className="nedaa">
      <section className="oq45n">
        <div className="bvaeg"></div>
        <div className="dvq2n">
          <div className="luu5j">
            <div className="wdilk">
              <h1 className="mipdb">
                Топ 100
                <span className="gtgad">Инфобизнесменов</span>
                России
              </h1>
              <p className="f0vbk">Рейтинг самых успешных и влиятельных
                предпринимателей в сфере информационного бизнеса</p>
              <div className="gxuos">
                <a className="cytwc z5wia l0lfi"
                  href="#professionals">Смотреть рейтинг</a>
                <a className="cytwc z5wia defuo ww2r5 l0lfi"
                  href="#about">Узнать больше</a>
              </div>
            </div>
            <div className="f0rwk">
              <div
                className="pvwjw">
                <div
                  className="oip0h">
                  <div className="a1cud">
                    <div
                      className="lnubu">
                      <i data-lucide="users" className="jv3gs"></i>
                      <span className="z5wia eb2y2">100+ Профессионалов</span>
                    </div>
                    <div
                      className="lnubu">
                      <i data-lucide="trending-up" className="jv3gs"></i>
                      <span className="z5wia eb2y2">Актуальный рейтинг</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
      <section id="professionals" className="a3v33">
        <div className="x0khv">
          <div className="valvo">
            <div className="io0tv">
              <h2 className="wj1yt">Рейтинг инфобизнесменов</h2>
              <p className="d25kj">Ознакомьтесь с нашим рейтингом самых успешных
                предпринимателей в сфере информационного бизнеса</p>
            </div>
          </div>
          <div id="businessmenContainer"
            className="ixmdj">
          </div>
          <div className="pod42"><a
              className="cytwc z5wia defuo ww2r5 l0lfi"
              href="/all-professionals">Показать всех 100 инфобизнесменов</a></div>
        </div>
      </section>
      <section className="a3v33">
        <div className="x0khv">
          <div className="valvo">
            <div className="io0tv">
              <h2 className="wj1yt">Лента инфобиза</h2>
              <p className="d25kj">Последние видео от ведущих инфобизнесменов
                России</p>
            </div>
          </div>
          <div id="newsContainer" className="ixmdj">
          </div>
          <div className="pod42"><a
              className="cytwc z5wia defuo ww2r5 l0lfi"
              href="/videos">Смотреть все видео</a></div>
        </div>
      </section>
      <section id="about" className="zcexf">
        <div className="x0khv">
          <div className="valvo">
            <div className="io0tv">
              <h2 className="wj1yt">О проекте</h2>
              <p className="d25kj">Наш рейтинг составлен на основе объективных показателей
                успешности</p>
            </div>
          </div>
          <div className="gmbf0">
            <div className="xupda">
              <div className="oneuv">Методология
              </div>
              <h3 className="lkbad">Как мы составляем рейтинг</h3>
              <p className="lo33d">Наш рейтинг основан на комплексной оценке
                различных факторов, включая доход, охват аудитории, влияние в социальных сетях,
                качество контента и отзывы клиентов. Мы регулярно обновляем данные, чтобы предоставить вам самую
                актуальную информацию о лидерах инфобиза.</p>
            </div>
            <div className="xupda">
              <div className="oneuv">Критерии</div>
              <h3 className="lkbad">Что мы оцениваем</h3>
              <ul className="kza52">
                <li className="eb5z2">
                  <div className="rkc1k">
                    <i data-lucide="check" className="xdgad"></i>
                  </div>Годовой доход от информационных продуктов
                </li>
                <li className="eb5z2">
                  <div className="rkc1k">
                    <i data-lucide="check" className="xdgad"></i>
                  </div>Размер и вовлеченность аудитории
                </li>
                <li className="eb5z2">
                  <div className="rkc1k">
                    <i data-lucide="check" className="xdgad"></i>
                  </div>Качество и уникальность контента
                </li>
                <li className="eb5z2">
                  <div className="rkc1k">
                    <i data-lucide="check" className="xdgad"></i>
                  </div>Отзывы и удовлетворенность клиентов
                </li>
              </ul>
            </div>
          </div>
        </div>
      </section>
    </main>
    <footer className="drrdt">
      <div className="x0khv">
        <div className="oohxc">
          <div className="fd4z2"><i data-lucide="circle" className="xbhad"></i><span
              className="rlbad">Топ 100 Инфобизнесменов</span></div>
          <p className="hrzmc">2025 Топ 100 Инфобизнесменов. Все права
            защищены.</p>
          <div className="z2cda"><a
              className="z5wia l0lfi"
              href="#">Политика конфиденциальности</a><a
              className="z5wia l0lfi" href="#">Условия
              использования</a></div>
        </div>
      </div>
    </footer>
  </div>
  <div id="mobileMenu" 
    className="vnpuy"
    aria-hidden="true">
    <div className="awrkl">
      <div className="ci24y" data-state="closed">
        <div className="vagfv">
          <div className="egnl2"></div>
          <div className="cqqhd">
            <nav className="kmcda">
              <a className="defuo l0lfi" href="/">
                <i data-lucide="home" className="xbhad"></i>
                Главная
              </a>
              <a className="defuo l0lfi" href="#professionals">
                <i data-lucide="users" className="xbhad"></i>
                Рейтинг
              </a>
              <a className="defuo l0lfi" href="/videos">
                <i data-lucide="play" className="xbhad"></i>
                Лента
              </a>
              <a className="defuo l0lfi" href="#about">
                <i data-lucide="info" className="xbhad"></i>
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
        <div className="et0wm">
          <div className="dl2bt">
            <div className="spvlw">
              <div className="rd1ym">
                #${data.rank}
              </div>
            </div>
            <img alt="${data.name}" 
              src="${data.image}" 
              className="mj5do"
            />
          </div>
          <div className="oodaa">
            <h3 className="jszw1">${data.name}</h3>
            <p className="yudl2">${data.category}</p>
            <div className="gn5s5">
              <div>
                <p className="hdvrl">Годовой доход</p>
                <p className="r5w41">${data.revenue}</p>
              </div>
              <div>
                <p className="hdvrl">Аудитория</p>
                <p className="r5w41">${data.subscribers}</p>
              </div>
            </div>
            <div className="cddtx">
              ${data.tags.map(tag => `
                <span className="qusoe">
                  ${tag}
                </span>
              `).join('')}
            </div>
          </div>
          <div className="ecc03">
            <div className="v2cda">
              ${Object.entries(data.socialLinks).map(([platform, url]) => `
                <a href="${url}" 
                  target="_blank" 
                  rel="noopener noreferrer" 
                  className="cytwc l0lfi"
                >
                  <span className="ksdaa">${platform}</span>
                  ${getSocialIcon(platform)}
                </a>
              `).join('')}
            </div>
            <a href="${data.profileUrl}" 
              className="cytwc ufufg defuo ww2r5 l0lfi"
            >
              Подробнее
            </a>
          </div>
        </div>
      `],
      ['news', (data) => `
        <div className="flfgq">
          <div className="qc5mm">
            <div className="ic3aa"></div>
            <div className="q5rnb">
              <button className="cytwc z5wia yu4ca">
                <i data-lucide="play" className="x5had"></i>
                <span className="ksdaa">Смотреть видео</span>
              </button>
            </div>
            <div className="baks2">
              ${data.duration || '15:18'}
            </div>
            <div className="rj2hx">
              <div className="yg4wx">
                <i data-lucide="youtube" className="xffad"></i>
                <span>${data.platform || 'YouTube'}</span>
              </div>
            </div>
            <img 
              alt="${data.title}" 
              loading="lazy"
              decoding="async"
              className="qoe2s"
              src="${data.image}"
            >
          </div>
          <div className="podaa">
            <a className="vcdaa" href="${data.url || '#'}">
              <h3 className="pdxj1">
                ${data.title}
              </h3>
            </a>
            <div className="t1rfx">
              <div className="fd4z2">
                <span className="sl1ss">
                  <img 
                    className="xecj5" 
                    alt="${data.author}"
                    src="${data.authorImage || 'https://v0-topchik-infobiza.vercel.app/placeholder.svg?height=100&width=100'}"
                  >
                </span>
                <a className="z5wia l0lfi" href="${data.authorUrl || '#'}">
                  ${data.author}
                </a>
              </div>
            </div>
          </div>
          <div className="j1hfq">
            <div className="fd4z2">
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

      return `<i data-lucide="${iconMap[platform]}" className="xdgad"></i>`;
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