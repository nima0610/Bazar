<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop From Home</title>
    <link rel="stylesheet" href="styles.css">
    </style>
    <!-- Add Font Awesome for icon -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />

    <!-- Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>

</head>

<body>
    <div class="main">
        <div class="side_options">
            <a href="#">Become a Seller</a>
            <a href="#">Login</a>
            <a href="#">Signup</a>
            <a href="#">Help and Support</a>
        </div>
        <div class="logo_main">
            <img src="assets/bazari.png">
        </div>
        <div class="search_bar">
            <form method="POST">
                <input type="text" name="searcher" placeholder="Search Products in Bazar">
                <button class="search-button">🔍</button>
            </form>
        </div>
    </div>

    <section id="slider-1">
        <div class="swiper" id="swiper-1">
            <div class="swiper-wrapper">
                <div class="swiper-slide">
                    <img src="img/shop.jpg" alt="Spain" />
                </div> <!-- end swiper-slide -->
                <div class="swiper-slide">
                    <img src="img/shoppp.webp" alt="Spain" />
                </div> <!-- end swiper-slide -->
                <div class="swiper-slide">
                    <img src="img/toys.jpg" alt="Spain" />
                </div> <!-- end swiper-slide -->
                <div class="swiper-slide">
                    <img src="img/game.jpg" alt="Spain" />
                </div> <!-- end swiper-slide -->
            </div>
            <div class="swiper-pagination"></div>
        </div> <!-- end swiper -->
    </section>
    <script src="app.js"></script>
</body>

</html>