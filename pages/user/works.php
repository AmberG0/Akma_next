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

<?php include 'includes/footer.php'; ?>
