<?php
session_start();
require_once '../../i/WebsiteBackend/db.php';

$page_title = "Наши работы";
include 'includes/header.php';

// Получаем опубликованные отзывы случайным образом
try {
    $stmt = $pdo->query("SELECT * FROM Reviews WHERE Is_published = 'да' ORDER BY RAND() LIMIT 10");
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $reviews = [];
}

// Обработка успешной отправки
$success_message = '';
if (isset($_SESSION['review_success'])) {
    $success_message = $_SESSION['review_success'];
    unset($_SESSION['review_success']);
}

// Обработка ошибки
$error_message = '';
if (isset($_SESSION['review_error'])) {
    $error_message = $_SESSION['review_error'];
    unset($_SESSION['review_error']);
}
?>

<!-- Hero секция -->
<section class="works-hero">
    <div class="container">
        <h1>Наши работы и отзывы</h1>
        <p>Реальные проекты, выполненные нашей командой</p>
    </div>
</section>

<div class="container works-container">
    <?php if ($success_message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <!-- Секция отзывов -->
    <section class="reviews-section">
        <h2 class="section-title-main">Отзывы наших клиентов</h2>
        
        <?php if (empty($reviews)): ?>
            <div class="no-reviews">
                <p>Пока нет опубликованных отзывов</p>
            </div>
        <?php else: ?>
            <div class="reviews-grid">
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <div class="review-author">
                                <span class="author-icon">👤</span>
                                <span class="author-name"><?= htmlspecialchars($review['Client_name']) ?></span>
                            </div>
                            <div class="review-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="star <?= $i <= $review['Rating'] ? 'filled' : '' ?>">★</span>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="review-text">
                            <?= nl2br(htmlspecialchars($review['Review_text'])) ?>
                        </div>
                        <div class="review-date">
                            <?= date('d.m.Y', strtotime($review['Date_created'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Форма добавления отзыва -->
    <section class="add-review-section">
        <h2 class="section-title-main">Оставить отзыв</h2>
        <div class="review-form-wrapper">
            <form method="POST" action="submit_review.php" class="review-form">
                <div class="form-row">
                    <div class="form-group-full">
                        <label for="client_name">Ваше имя *</label>
                        <input type="text" id="client_name" name="client_name" required placeholder="Иван Иванов">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group-full">
                        <label for="review_text">Текст отзыва *</label>
                        <textarea id="review_text" name="review_text" rows="5" required placeholder="Расскажите о вашем опыте работы с нами..."></textarea>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group-full">
                        <label>Ваша оценка *</label>
                        <div class="rating-input">
                            <input type="radio" name="rating" id="star5" value="5" required>
                            <label for="star5" title="5 звезд">★</label>
                            
                            <input type="radio" name="rating" id="star4" value="4">
                            <label for="star4" title="4 звезды">★</label>
                            
                            <input type="radio" name="rating" id="star3" value="3">
                            <label for="star3" title="3 звезды">★</label>
                            
                            <input type="radio" name="rating" id="star2" value="2">
                            <label for="star2" title="2 звезды">★</label>
                            
                            <input type="radio" name="rating" id="star1" value="1">
                            <label for="star1" title="1 звезда">★</label>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group-full">
                        <button type="submit" class="btn-submit-review">Отправить отзыв</button>
                    </div>
                </div>
                
                <p class="form-note">* Отзыв будет опубликован после модерации</p>
            </form>
        </div>
    </section>
</div>

<style>
/* Hero секция */
.works-hero {
    background: linear-gradient(135deg, #1A1A1A 0%, #333 100%);
    color: #FFFFFF;
    padding: 80px 0;
    text-align: center;
    margin-bottom: 40px;
}

.works-hero h1 {
    font-size: 42px;
    margin-bottom: 15px;
    font-weight: 700;
}

.works-hero p {
    font-size: 18px;
    opacity: 0.9;
}

.works-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

/* Заголовки секций */
.section-title-main {
    font-size: 32px;
    color: #1A1A1A;
    text-align: center;
    margin-bottom: 40px;
    position: relative;
    padding-bottom: 15px;
}

.section-title-main::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 4px;
    background-color: #FFD700;
}

/* Сетка отзывов */
.reviews-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 25px;
    margin-bottom: 60px;
}

/* Карточка отзыва */
.review-card {
    background: #FFFFFF;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    border-left: 4px solid #FFD700;
}

.review-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #f0f0f0;
}

.review-author {
    display: flex;
    align-items: center;
    gap: 10px;
}

.author-icon {
    font-size: 24px;
}

.author-name {
    font-weight: 600;
    color: #1A1A1A;
    font-size: 16px;
}

.review-rating {
    display: flex;
    gap: 3px;
}

.star {
    color: #ddd;
    font-size: 20px;
}

.star.filled {
    color: #FFD700;
}

.review-text {
    color: #555;
    line-height: 1.6;
    margin-bottom: 15px;
    font-size: 14px;
}

.review-date {
    color: #999;
    font-size: 12px;
    text-align: right;
}

.no-reviews {
    text-align: center;
    padding: 60px 20px;
    color: #999;
    font-size: 16px;
}

/* Форма отзыва */
.add-review-section {
    background: #f9f9f9;
    padding: 50px 30px;
    border-radius: 12px;
    margin-top: 40px;
}

.review-form-wrapper {
    max-width: 700px;
    margin: 0 auto;
}

.review-form .form-row {
    margin-bottom: 20px;
}

.form-group-full {
    width: 100%;
}

.form-group-full label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
}

.form-group-full input[type="text"],
.form-group-full textarea {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 15px;
    transition: border-color 0.3s;
    box-sizing: border-box;
}

.form-group-full input[type="text"]:focus,
.form-group-full textarea:focus {
    outline: none;
    border-color: #FFD700;
}

.form-group-full textarea {
    resize: vertical;
    min-height: 120px;
}

/* Рейтинг в форме */
.rating-input {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
    gap: 5px;
}

.rating-input input[type="radio"] {
    display: none;
}

.rating-input label {
    font-size: 32px;
    color: #ddd;
    cursor: pointer;
    transition: color 0.2s;
}

.rating-input label:hover,
.rating-input label:hover ~ label,
.rating-input input[type="radio"]:checked ~ label {
    color: #FFD700;
}

.btn-submit-review {
    background: linear-gradient(135deg, #FFD700 0%, #ffcc00 100%);
    color: #1A1A1A;
    padding: 14px 35px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    width: 100%;
}

.btn-submit-review:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(255, 215, 0, 0.4);
}

.form-note {
    text-align: center;
    color: #999;
    font-size: 13px;
    margin-top: 15px;
}

/* Алерты */
.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 25px;
    font-weight: 500;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border-left: 4px solid #28a745;
}

.alert-error {
    background-color: #f8d7da;
    color: #721c24;
    border-left: 4px solid #dc3545;
}

/* Адаптивность */
@media (max-width: 768px) {
    .works-hero h1 {
        font-size: 32px;
    }
    
    .works-hero p {
        font-size: 16px;
    }
    
    .reviews-grid {
        grid-template-columns: 1fr;
    }
    
    .section-title-main {
        font-size: 26px;
    }
    
    .add-review-section {
        padding: 30px 20px;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
