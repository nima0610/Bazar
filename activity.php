<?php
session_start();


if (isset($_SESSION['user_id'])) {
    echo $_SESSION['user_id'];
} else {
    echo "NO CUSTOMER FOUND OF THIS USER ID";
}
?>