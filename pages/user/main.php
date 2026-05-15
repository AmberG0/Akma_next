<?php
/**
 * Главная страница сайта
 * ИП Барабарян Карен Аветикович
 */

$pageTitle = 'СтройСервис - Главная | ИП Барабарян К.А.';

include 'includes/header.php';
require_once '../../i/WebsiteBackend/db.php';
?>

<main>
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container hero-container">
            <div class="hero-slider">
                <div class="slide active" data-index="0">
                    <div class="slide-content">
                        <h3>Земляные работы</h3>
                        <p>Аренда спецтехники и выполнение работ любой сложности</p>
                    </div>
                </div>
                <div class="slide" data-index="1">
                    <div class="slide-content">
                        <h3>Строительство под ключ</h3>
                        <p>От фундамента до кровли с гарантией качества</p>
                    </div>
                </div>
                <div class="slide" data-index="2">
                    <div class="slide-content">
                        <h3>Благоустройство</h3>
                        <p>Комплексное благоустройство территорий</p>
                    </div>
                </div>
                
                <div class="slider-controls">
                    <button class="prev-slide">&lt;</button>
                    <div class="indicators">
                        <span class="indicator active" data-slide="0"></span>
                        <span class="indicator" data-slide="1"></span>
                        <span class="indicator" data-slide="2"></span>
                    </div>
                    <button class="next-slide">&gt;</button>
                </div>
            </div>
            
            <div class="hero-info">
                <h2>Почему выбирают нас</h2>
                <div class="info-block">
                    <h3 id="info-title">Земляные работы</h3>
                    <p id="info-desc">Используем современную спецтехнику. Работаем быстро и точно в срок. Соблюдаем все технические нормы.</p>
                    <ul class="features-list">
                        <li>✓ Опыт работы более 15 лет</li>
                        <li>✓ Собственный парк техники</li>
                        <li>✓ Работаем по договору</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about-section">
        <div class="container">
            <h2>О компании</h2>
            <div class="about-content">
                <p>ИП Барабарян Карен Аветикович успешно работает на рынке строительных услуг с 2007 года. Мы специализируемся на выполнении комплексных строительных работ, аренде спецтехники и благоустройстве территорий.</p>
                <p>Наша команда состоит из квалифицированных специалистов: прорабов, трактористов и разнорабочих, готовых выполнить заказ любой сложности в установленные сроки.</p>
                <div class="stats-grid">
                    <div class="stat-item">
                        <span class="stat-number">15+</span>
                        <span class="stat-label">Лет опыта</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">200+</span>
                        <span class="stat-label">Выполненных объектов</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">100%</span>
                        <span class="stat-label">Гарантия качества</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
