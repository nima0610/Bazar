<!DOCTYPE HTML>
<html>

<head>
    <title>Seller Dashboard</title>
    <link rel="stylesheet" href="../seller/style.css">
    <style>
        .content-section {
            display: none;
        }

        .content-section.active {
            display: block;
        }
    </style>
</head>

<body>
    <div class="main_box">
        <div class="internal_box">
            <img src="../seller/img/bazari.png">
            <h2>Seller Dashboard</h2>
            <form method="POST" action="dashboard.php">
                <input type="text" name="searchbox" placeholder="🔍 Search for Products">
            </form>
            <p id="currentDate"></p>
        </div>
    </div>

    <div class="page_divider">
        <div class="left_divided">
            <button class="sidebar-btn" data-target="dashboard">Dashboards</button>
            <button class="sidebar-btn" data-target="orders">Orders</button>
            <button class="sidebar-btn" data-target="products">Products</button>
            <button class="sidebar-btn" data-target="analytics">Sell Product</button>
            <button class="sidebar-btn" data-target="customers">Customers</button>
            <button class="sidebar-btn" data-target="profile">Profile</button>
        </div>

        <div class="main_part">
            <div id="dashboard" class="content-section active">
                <div class="grid-container dashboard-grid">
                    <!-- to put grid item in left side of upper part -->
                    <div class="grid-item"><img src="../img/chart1.webp"></div>
                    <!-- to put grid item in right side of upper part (4 images) -->
                    <div class="grid-item nested-grid-container">
                        <div class="nested-grid">
                            <div class="nested-item"><img src="../img/chart2.webp"></div>
                            <div class="nested-item"><img src="../img/chart3.jpg"></div>
                            <div class="nested-item"><img src="../img/chart4.jpg"></div>
                            <div class="nested-item"><img src="../img/chart5.jpg"></div>
                        </div>
                    </div>
                    <div class="grid-item"><img src="../img/chart6.jpg"></div>
                    <div class="grid-item"><img src="../img/chart7.jpg"></div>
                </div>
            </div>
            <div id="orders" class="content-section">
                <div class="grid-container orders-grid">
                    <div class="grid-item"><img src="../img/game.jpg" width="300">
                    </div>
                    <div class="grid-item"><img src="../img/game.jpg" width="300">
                    </div>
                    <div class="grid-item"><img src="../img/game.jpg" width="300">
                    </div>
                </div>
            </div>
            <!-- Add other sections if needed -->
        </div>
    </div>

    <script>
        document.querySelectorAll('.sidebar-btn').forEach(button => {
            button.addEventListener('click', () => {
                console.log("Button Clicked: " + button.innerText);
                console.log("Target Section: " + button.getAttribute('data-target'));

                // Hide all content sections
                document.querySelectorAll('.content-section').forEach(section => {
                    section.classList.remove('active');
                });

                // Show the target section
                const target = button.getAttribute('data-target');
                const targetSection = document.getElementById(target);
                if (targetSection) {
                    targetSection.classList.add('active');
                } else {
                    console.warn("No section found with id: " + target);
                }
            });
        });
    </script>

    <script>
        // Date Script
        const today = new Date();
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        const formattedDate = today.toLocaleDateString(undefined, options);
        document.getElementById("currentDate").innerText = formattedDate;
    </script>
</body>

</html>