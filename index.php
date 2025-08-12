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
            <a href="login.php">Login</a>
            <a href="registration.php">Signup</a>
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

    <div class="flash">
        <h1>Flash Sale</h1>
    </div>

    <div class="advertisement">
        <div class="grid-container dashboard-grid">
            <div class="grid-item">
                <div class="image-container">
                    <img src="img/shoes.jpg" alt="Product Image" />
                </div>
                <div class="info-container">
                    <h3>Summer Shoes</h3>
                    <p style="color:red; font-size: 24px; position: relative; left: 10px;">Rs 2500</p>
                    <p style="color:red; font-size: 20px;text-decoration: line-through;
    color: gray;">Rs 2750</p>
                </div>
            </div>
            <div class="grid-item">
                <div class="image-container">
                    <img src="img/uno.jpg" alt="Product Image" />
                </div>
                <div class="info-container">
                    <h3>Premium UNO cards</h3>
                    <p style="color:red; font-size: 24px; position: relative; left: 10px;">Rs 500</p>
                    <p style="color:red; font-size: 20px;text-decoration: line-through;
    color: gray;">Rs 750</p>
                </div>
            </div>
            <div class="grid-item">
                <div class="image-container">
                    <img src="img/jacket.jpg" alt="Product Image" />
                </div>
                <div class="info-container">
                    <h3>Army Jacket</h3>
                    <p style="color:red; font-size: 24px; position: relative; left: 10px;">Rs 5500</p>
                    <p style="color:red; font-size: 20px;text-decoration: line-through;
    color: gray;">Rs 6750</p>
                </div>
            </div>
            <div class="grid-item">
                <div class="image-container">
                    <img src="img/guitar.jpg" alt="Product Image" />
                </div>
                <div class="info-container">
                    <h3>Cort Guitar</h3>
                    <p style="color:red; font-size: 24px; position: relative; left: 10px;">Rs 25000</p>
                    <p style="color:red; font-size: 20px;text-decoration: line-through;
    color: gray;">Rs 27999</p>
                </div>
            </div>
            <div class="grid-item">
                <div class="image-container">
                    <img src="img/gloves.avif" alt="Product Image" />
                </div>
                <div class="info-container">
                    <h3>Oven Gloves</h3>
                    <p style="color:red; font-size: 24px; position: relative; left: 10px;">Rs 2449</p>
                    <p style="color:red; font-size: 20px;text-decoration: line-through;
    color: gray;">Rs 2999</p>
                </div>
            </div>
            <div class="grid-item">
                <div class="image-container">
                    <img src="img/cycle.jpg" alt="Product Image" />
                </div>
                <div class="info-container">
                    <h3>Kid's Cycle</h3>
                    <p style="color:red; font-size: 24px; position: relative; left: 10px;">Rs 8199</p>
                    <p style="color:red; font-size: 20px;text-decoration: line-through;
    color: gray;">Rs 9899</p>
                </div>
            </div>
        </div>
        <script src="app.js"></script>
</body>

</html>