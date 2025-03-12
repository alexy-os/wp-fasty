<!DOCTYPE html>
<html lang="ru" className="edaaa">

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

<body className="ozo0y">
  <div className="lcllc">
    <header className="xoyrn">
      <div className="ezmjn">
        <a className="wolpe" href="/">
          <i data-lucide="award" className="lbhad"></i>
          <span>Топ 100 Инфобизнесменов</span>
        </a>
        <div className="hhjm0">
          <div className="bwoda">
            <i data-lucide="search" className="mmayp"></i>
            <input type="search"
              className="4tgiy"
              placeholder="Поиск по имени...">
          </div>
        </div>
        <nav className="plm5c"><a className="55wia x0lfi"
            href="/">Главная</a><a className="55wia x0lfi"
            href="#professionals">Рейтинг</a><a
            className="55wia x0lfi" href="/videos">Лента</a><a
            className="55wia x0lfi" href="#about">О проекте</a></nav>
        <div className="n55z2"><button onclick="toggleDarkMode()"
            className="wytwc uu4ca">
            <i data-lucide="sun"
              className="4x5ar"></i>
            <i data-lucide="moon"
              className="ciosr"></i>
            <span className="2sdaa">Переключить тему</span>
          </button><button
            id="mobileMenuButton"
            className="wytwc 55wia xefuo uu4ca">
            <span className="2sdaa">Открыть меню</span>
            <i data-lucide="menu" className="lbhad"></i>
          </button></div>
      </div>
      <div className="infoq">
        <div className="3bshj">
          <div className="bwoda"><i data-lucide="search"
              className=" absolute left-3 top-2.5 h-4 w-4 text-muted-foreground"></i><input type="search"
              className="4tgiy"
              placeholder="Поиск по имени..."></div>
        </div>
      </div>
    </header>
    <main className="ledaa">
      <section className="mq45n">
        <div className="3vaeg"></div>
        <div className="pvq2n">
          <div className="luu5j">
            <div className="adilk">
              <h1 className="kipdb">
                Топ 100
                <span className="0tgad">Инфобизнесменов</span>
                России
              </h1>
              <p className="j0vbk">Рейтинг самых успешных и влиятельных
                предпринимателей в сфере информационного бизнеса</p>
              <div className="axuos">
                <a className="wytwc 55wia x0lfi"
                  href="#professionals">Смотреть рейтинг</a>
                <a className="wytwc 55wia xefuo 2w2r5 x0lfi"
                  href="#about">Узнать больше</a>
              </div>
            </div>
            <div className="f0rwk">
              <div
                className="vvwjw">
                <div
                  className="qip0h">
                  <div className="01cud">
                    <div
                      className="rnubu">
                      <i data-lucide="users" className="nv3gs"></i>
                      <span className="55wia 2b2y2">100+ Профессионалов</span>
                    </div>
                    <div
                      className="rnubu">
                      <i data-lucide="trending-up" className="nv3gs"></i>
                      <span className="55wia 2b2y2">Актуальный рейтинг</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
      <section id="professionals" className="m3v33">
        <div className="b0khv">
          <div className="3alvo">
            <div className="2o0tv">
              <h2 className="0j1yt">Рейтинг инфобизнесменов</h2>
              <p className="v25kj">Ознакомьтесь с нашим рейтингом самых успешных
                предпринимателей в сфере информационного бизнеса</p>
            </div>
          </div>
          <div id="businessmenContainer"
            className="yxmdj">
          </div>
          <div className="dod42"><a
              className="wytwc 55wia xefuo 2w2r5 x0lfi"
              href="/all-professionals">Показать всех 100 инфобизнесменов</a></div>
        </div>
      </section>
      <section className="m3v33">
        <div className="b0khv">
          <div className="3alvo">
            <div className="2o0tv">
              <h2 className="0j1yt">Лента инфобиза</h2>
              <p className="v25kj">Последние видео от ведущих инфобизнесменов
                России</p>
            </div>
          </div>
          <div id="newsContainer" className="yxmdj">
          </div>
          <div className="dod42"><a
              className="wytwc 55wia xefuo 2w2r5 x0lfi"
              href="/videos">Смотреть все видео</a></div>
        </div>
      </section>
      <section id="about" className="5cexf">
        <div className="b0khv">
          <div className="3alvo">
            <div className="2o0tv">
              <h2 className="0j1yt">О проекте</h2>
              <p className="v25kj">Наш рейтинг составлен на основе объективных показателей
                успешности</p>
            </div>
          </div>
          <div className="smbf0">
            <div className="pupda">
              <div className="yneuv">Методология
              </div>
              <h3 className="fkbad">Как мы составляем рейтинг</h3>
              <p className="po33d">Наш рейтинг основан на комплексной оценке
                различных факторов, включая доход, охват аудитории, влияние в социальных сетях,
                качество контента и отзывы клиентов. Мы регулярно обновляем данные, чтобы предоставить вам самую
                актуальную информацию о лидерах инфобиза.</p>
            </div>
            <div className="pupda">
              <div className="yneuv">Критерии</div>
              <h3 className="fkbad">Что мы оцениваем</h3>
              <ul className="0za52">
                <li className="mb5z2">
                  <div className="1kc1k">
                    <i data-lucide="check" className="jdgad"></i>
                  </div>Годовой доход от информационных продуктов
                </li>
                <li className="mb5z2">
                  <div className="1kc1k">
                    <i data-lucide="check" className="jdgad"></i>
                  </div>Размер и вовлеченность аудитории
                </li>
                <li className="mb5z2">
                  <div className="1kc1k">
                    <i data-lucide="check" className="jdgad"></i>
                  </div>Качество и уникальность контента
                </li>
                <li className="mb5z2">
                  <div className="1kc1k">
                    <i data-lucide="check" className="jdgad"></i>
                  </div>Отзывы и удовлетворенность клиентов
                </li>
              </ul>
            </div>
          </div>
        </div>
      </section>
    </main>
    <footer className="nrrdt">
      <div className="b0khv">
        <div className="qohxc">
          <div className="ld4z2"><i data-lucide="circle" className="lbhad"></i><span
              className="5lbad">Топ 100 Инфобизнесменов</span></div>
          <p className="1rzmc">2025 Топ 100 Инфобизнесменов. Все права
            защищены.</p>
          <div className="v2cda"><a
              className="55wia x0lfi"
              href="#">Политика конфиденциальности</a><a
              className="55wia x0lfi" href="#">Условия
              использования</a></div>
        </div>
      </div>
    </footer>
  </div>
  <div id="mobileMenu" 
    className="dnpuy"
    aria-hidden="true">
    <div className="ywrkl">
      <div className="si24y" data-state="closed">
        <div className="1agfv">
          <div className="4gnl2"></div>
          <div className="oqqhd">
            <nav className="ymcda">
              <a className="xefuo x0lfi" href="/">
                <i data-lucide="home" className="lbhad"></i>
                Главная
              </a>
              <a className="xefuo x0lfi" href="#professionals">
                <i data-lucide="users" className="lbhad"></i>
                Рейтинг
              </a>
              <a className="xefuo x0lfi" href="/videos">
                <i data-lucide="play" className="lbhad"></i>
                Лента
              </a>
              <a className="xefuo x0lfi" href="#about">
                <i data-lucide="info" className="lbhad"></i>
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
        <div className="kt0wm">
          <div className="zl2bt">
            <div className="apvlw">
              <div className="jd1ym">
                #${data.rank}
              </div>
            </div>
            <img alt="${data.name}" 
              src="${data.image}" 
              className="qj5do"
            />
          </div>
          <div className="eodaa">
            <h3 className="dszw1">${data.name}</h3>
            <p className="yudl2">${data.category}</p>
            <div className="en5s5">
              <div>
                <p className="bdvrl">Годовой доход</p>
                <p className="d5w41">${data.revenue}</p>
              </div>
              <div>
                <p className="bdvrl">Аудитория</p>
                <p className="d5w41">${data.subscribers}</p>
              </div>
            </div>
            <div className="wddtx">
              ${data.tags.map(tag => `
                <span className="0usoe">
                  ${tag}
                </span>
              `).join('')}
            </div>
          </div>
          <div className="ecc03">
            <div className="r2cda">
              ${Object.entries(data.socialLinks).map(([platform, url]) => `
                <a href="${url}" 
                  target="_blank" 
                  rel="noopener noreferrer" 
                  className="wytwc x0lfi"
                >
                  <span className="2sdaa">${platform}</span>
                  ${getSocialIcon(platform)}
                </a>
              `).join('')}
            </div>
            <a href="${data.profileUrl}" 
              className="wytwc 0fufg xefuo 2w2r5 x0lfi"
            >
              Подробнее
            </a>
          </div>
        </div>
      `],
      ['news', (data) => `
        <div className="zlfgq">
          <div className="ic5mm">
            <div className="kc3aa"></div>
            <div className="i5rnb">
              <button className="wytwc 55wia uu4ca">
                <i data-lucide="play" className="n5had"></i>
                <span className="2sdaa">Смотреть видео</span>
              </button>
            </div>
            <div className="xaks2">
              ${data.duration || '15:18'}
            </div>
            <div className="bj2hx">
              <div className="cg4wx">
                <i data-lucide="youtube" className="hffad"></i>
                <span>${data.platform || 'YouTube'}</span>
              </div>
            </div>
            <img 
              alt="${data.title}" 
              loading="lazy"
              decoding="async"
              className="yoe2s"
              src="${data.image}"
            >
          </div>
          <div className="fodaa">
            <a className="fcdaa" href="${data.url || '#'}">
              <h3 className="pdxj1">
                ${data.title}
              </h3>
            </a>
            <div className="b1rfx">
              <div className="ld4z2">
                <span className="el1ss">
                  <img 
                    className="becj5" 
                    alt="${data.author}"
                    src="${data.authorImage || 'https://v0-topchik-infobiza.vercel.app/placeholder.svg?height=100&width=100'}"
                  >
                </span>
                <a className="55wia x0lfi" href="${data.authorUrl || '#'}">
                  ${data.author}
                </a>
              </div>
            </div>
          </div>
          <div className="f1hfq">
            <div className="ld4z2">
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

      return `<i data-lucide="${iconMap[platform]}" className="jdgad"></i>`;
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