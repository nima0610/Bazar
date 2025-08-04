<?php



?>




<!DOCTYPE HTML>
<html>

<head>
    <title>Seller Dashboard</title>
    <link rel="stylesheet" href="../seller/style.css">
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
            <button>Dashboards</button>
            <button>Orders</button>
            <button>Products</button>
            <button>Analytics</button>
            <button>Customers</button>
            <button>Profile</button>
        </div>

        <div class="main_part">
            <img src="../img/bg.jpg">
        </div>
    </div>




    <script>
        // Get today's date
        const today = new Date();

        // Format the date as desired (e.g., YYYY-MM-DD or a readable format)
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        const formattedDate = today.toLocaleDateString(undefined, options);

        // Put it inside the HTML element
        document.getElementById("currentDate").innerText = formattedDate;
    </script>
</body>

</html>