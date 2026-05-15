/**
 * Основной JavaScript файл
 * ИП Барабарян К.А.
 * Специальность 09.02.07
 */

document.addEventListener('DOMContentLoaded', function() {
    // Инициализация темной темы
    initTheme();

    // Инициализация слайдера (если есть на странице)
    initSlider();
});

/**
 * Переключение темной темы
 */
function initTheme() {
    const themeToggle = document.getElementById('theme-toggle');
    const body = document.body;

    // Проверяем сохраненную тему
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        body.classList.add('dark-theme');
        if (themeToggle) {
            themeToggle.textContent = '☀️ Светлая тема';
        }
    }

    // Обработчик переключения
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            body.classList.toggle('dark-theme');

            if (body.classList.contains('dark-theme')) {
                localStorage.setItem('theme', 'dark');
                themeToggle.textContent = '☀️ Светлая тема';
            } else {
                localStorage.setItem('theme', 'light');
                themeToggle.textContent = '🌙 Темная тема';
            }
        });
    }
}

/**
 * Слайдер для Hero секции
 */
function initSlider() {
    const sliderContainer = document.querySelector('.hero-slider');
    if (!sliderContainer) return;

    const slides = document.querySelectorAll('.slide');
    const indicators = document.querySelectorAll('.indicator');
    const infoTitle = document.getElementById('info-title');
    const infoDesc = document.getElementById('info-desc');

    if (slides.length === 0 || indicators.length === 0) return;

    let currentSlide = 0;
    const slideInterval = 5000; // 5 секунд

    // Данные для слайдов
    const slidesData = [
        {
            title: "Земляные работы",
            desc: "Используем современную спецтехнику. Работаем быстро и точно в срок. Соблюдаем все технические нормы."
        },
        {
            title: "Строительство под ключ",
            desc: "Полный цикл строительных работ. От проектирования до сдачи объекта. Гарантия на все виды работ."
        },
        {
            title: "Благоустройство",
            desc: "Комплексное благоустройство территорий. Укладка плитки, озеленение, установка ограждений."
        }
    ];

    function showSlide(index) {
        slides.forEach(slide => slide.classList.remove('active'));
        indicators.forEach(ind => ind.classList.remove('active'));
        
        slides[index].classList.add('active');
        indicators[index].classList.add('active');
        
        // Обновление текста
        if (infoTitle && infoDesc) {
            infoTitle.textContent = slidesData[index].title;
            infoDesc.textContent = slidesData[index].desc;
        }
        
        currentSlide = index;
    }

    // Автоматическое переключение
    let timer = setInterval(() => {
        let next = (currentSlide + 1) % slides.length;
        showSlide(next);
    }, slideInterval);

    // Ручное переключение индикаторами
    indicators.forEach((ind, index) => {
        ind.addEventListener('click', () => {
            clearInterval(timer);
            showSlide(index);
            timer = setInterval(() => {
                let next = (currentSlide + 1) % slides.length;
                showSlide(next);
            }, slideInterval);
        });
    });

    // Переключение кнопками
    const prevBtn = document.querySelector('.prev-slide');
    const nextBtn = document.querySelector('.next-slide');

    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            clearInterval(timer);
            let prev = (currentSlide - 1 + slides.length) % slides.length;
            showSlide(prev);
            timer = setInterval(() => {
                let next = (currentSlide + 1) % slides.length;
                showSlide(next);
            }, slideInterval);
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            clearInterval(timer);
            let next = (currentSlide + 1) % slides.length;
            showSlide(next);
            timer = setInterval(() => {
                let next = (currentSlide + 1) % slides.length;
                showSlide(next);
            }, slideInterval);
        });
    }

    // Показываем первый слайд
    showSlide(0);
}
