    <script>
        const darkModeToggl = document.createElement('button');
        darkModeToggl.innerHTML = '🌓';
        darkModeToggl.className = 'fixed bottom-4 left-4 bg-slate-200 dark:bg-slate-700 p-2 rounded-full text-xs focus:outline-none transition duration-300 ease-in-out transform hover:scale-110';
        document.body.appendChild(darkModeToggl);
        darkModeToggl.addEventListener('click', () => {
            document.documentElement.classList.toggle('dark');
        });
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
        darkModeToggl.addEventListener('click', () => {
            if (document.documentElement.classList.contains('dark')) {
                localStorage.theme = 'dark';
            } else {
                localStorage.theme = 'light';
            }
        });
    </script>
    </body>
</html>