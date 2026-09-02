<?php
require_once 'config/db.php';
require_once 'ads/ad-units.php';
$device = get_device_type();

// Jika kamu ingin fitur filter tetap jalan, tapi defaultnya ACAK:
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'random'; // Set default ke random

$orderMap = [
    'popular' => 'views DESC',
    'random'  => 'RAND()', // Ini yang bikin acak setiap dibuka
    'new'     => 'created_at DESC'
];
$orderBy = isset($orderMap[$sort]) ? $orderMap[$sort] : 'RAND()';

try {
    // Gunakan ORDER BY RAND() agar urutan game selalu berubah tiap refresh
    $stmt = $pdo->prepare("SELECT title, slug, image_url FROM games WHERE status = 'publish' ORDER BY $orderBy LIMIT :limit");
    $stmt->bindValue(':limit', 21, PDO::PARAM_INT);
    $stmt->execute();
    $games = $stmt->fetchAll();
} catch (PDOException $e) {
    $games = [];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Play Online Games for Free</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <?php require_once 'ads/ads-header.php'; ?>
</head>
<body>

<?php
require_once 'header.php';
?>

<div class="filter-bar">
    <a href="?sort=new" class="filter-btn <?php echo ($sort == 'new') ? 'active' : ''; ?>">✨ New</a>
    <a href="?sort=popular" class="filter-btn <?php echo ($sort == 'popular') ? 'active' : ''; ?>">🔥 Popular</a>
    <a href="?sort=random" class="filter-btn <?php echo ($sort == 'random') ? 'active' : ''; ?>">🎲 Random</a>
</div>

<main class="main-container">
    <?php echo get_ad_unit('gm-header'); ?>
    <div id="game-grid" class="game-grid">
        <?php foreach ($games as $row): ?>
        <a href="game/<?php echo htmlspecialchars($row['slug']); ?>" class="game-card">
            <div class="card-inner">
                <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="<?php echo htmlspecialchars($row['title']); ?>" loading="lazy">
                <div class="game-title"><?php echo htmlspecialchars($row['title']); ?></div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    
    <div id="loader">🔄 Load more games...</div>
</main>


<?php
require_once 'footer.php';
?>


<script>
    let offset = 21;
    let loading = false;
    let noMoreData = false;
    const grid = document.getElementById('game-grid');
    const loader = document.getElementById('loader');
    const sortType = '<?php echo $sort; ?>';

    function loadMore() {
        if (loading || noMoreData) return;
        loading = true;
        loader.style.display = 'block';

        fetch(`api_load_more.php?offset=${offset}&sort=${sortType}`)
            .then(res => res.json())
            .then(data => {
                if (data && data.length > 0) {
                    data.forEach(game => {
                        const card = `
                            <a href="game/${game.slug}" class="game-card">
                                <div class="card-inner">
                                    <img src="${game.image_url}" alt="${game.title}" loading="lazy">
                                    <div class="game-title">${game.title}</div>
                                </div>
                            </a>`;
                        grid.insertAdjacentHTML('beforeend', card);
                    });
                    offset += 21;
                    loading = false;
                } else {
                    noMoreData = true;
                }
                loader.style.display = 'none';
            })
            .catch(() => {
                loader.style.display = 'none';
                loading = false;
            });
    }

    // Scroll event dengan toleransi lebih besar untuk Firefox Mac
    window.addEventListener('scroll', () => {
        const scrollHeight = document.documentElement.scrollHeight;
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const clientHeight = window.innerHeight;

        if (scrollTop + clientHeight >= scrollHeight - 1000) {
            loadMore();
        }
    }, { passive: true });
</script>
<?php echo get_ad_unit('gm-global'); ?>
</body>
</html>