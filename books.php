<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Books - BookShop</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Additional styles for book display */
        .search-bar {
            margin: 20px 0;
            text-align: center;
        }
        
        .search-bar input {
            width: 300px;
            padding: 10px;
            font-size: 16px;
        }
        
        .search-bar button {
            padding: 10px 20px;
            font-size: 16px;
        }
        
        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 30px;
            margin-top: 30px;
            padding: 20px;
        }
        
        .book-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s;
            text-align: center;
            padding: 15px;
        }
        
        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .book-card img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        
        .book-card h3 {
            font-size: 16px;
            margin: 10px 0 5px 0;
            height: 40px;
            overflow: hidden;
            color: #2c3e50;
        }
        
        .book-card .author {
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .book-card .price {
            font-size: 18px;
            color: #e74c3c;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .add-to-cart-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-size: 14px;
        }
        
        .add-to-cart-btn:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <header>
        <h1>BookShop</h1>
        <nav>
            <ul class="nav-list">
                <li><a href="index.html">Home</a></li>
                <li><a href="books.php">Books</a></li>
                <li><a href="cart.php">Cart</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="admin/login.php">Admin</a></li>
            </ul>
        </nav>
    </header>
    
    <main class="container">
        <h2>Browse Books</h2>
        
        <!-- Search form with SQL injection vulnerability -->
        <div class="search-bar">
            <form method="GET" action="books.php">
                <input type="text" name="search" placeholder="Search by title or author..." 
                       value="<?php echo $_GET['search'] ?? ''; ?>">
                <button type="submit">Search</button>
            </form>
        </div>
        
        <div class="books-grid">
            <?php
            require_once 'db_config.php';
            
            // INSECURE: Direct SQL concatenation allows SQL injection!
            $search = $_GET['search'] ?? '';
            $sql = "SELECT * FROM Products WHERE Title LIKE '%" . $search . "%' OR Author LIKE '%" . $search . "%' ORDER BY ProductID";
            
            $stmt = sqlsrv_query($conn, $sql);
            
            if ($stmt === false) {
                die(print_r(sqlsrv_errors(), true));
            }
            
            // Image mapping array - maps book titles to image files
            $imageMap = array(
                'The Midnight Garden' => 'midnight-garden.jpg',
                'Echoes of Tomorrow' => 'echoes-tomorrow.jpg',
                'The Last Bookkeeper' => 'last-bookkeeper.jpg',
                'Crimson Tides' => 'crimson-tides.jpg',
                'The Coffee Shop Chronicles' => 'coffee-shop.jpg',
                'The Digital Revolution' => 'digital-revolution.jpg',
                'Mindful Living' => 'mindful-living.jpg',
                'The Economics of Everything' => 'economics-everything.jpg',
                'Climate Action Now' => 'climate-action.jpg',
                'Mathematics Made Simple' => 'math-simple.jpg',
                'Introduction to Psychology' => 'intro-psychology.jpg',
                'World History: A Complete Guide' => 'world-history.jpg',
                'Programming Fundamentals' => 'programming-fundamentals.jpg',
                'Journey to the Stars' => 'journey-stars.jpg',
                'From Poverty to Purpose' => 'poverty-purpose.jpg',
                'The Innovator\'s Mind' => 'innovators-mind.jpg',
                'Breaking Barriers' => 'breaking-barriers.jpg'
            );
            
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                // Get the image filename or use a default
                $image = isset($imageMap[$row['Title']]) ? $imageMap[$row['Title']] : 'default-book.jpg';
                
                echo '<div class="book-card">';
                echo '<img src="images/' . $image . '" alt="' . htmlspecialchars($row['Title']) . '">';
                echo '<h3>' . htmlspecialchars($row['Title']) . '</h3>';
                echo '<p class="author">by ' . htmlspecialchars($row['Author']) . '</p>';
                echo '<p class="price">$' . number_format($row['Price'], 2) . '</p>';
                echo '<button class="add-to-cart-btn" onclick="addToCart(\'' . 
                     htmlspecialchars($row['Title'], ENT_QUOTES) . '\', ' . $row['Price'] . ')">Add to Cart</button>';
                echo '</div>';
            }
            
            sqlsrv_free_stmt($stmt);
            sqlsrv_close($conn);
            ?>
        </div>
    </main>
    
    <script>
        function addToCart(title, price) {
            let cart = JSON.parse(localStorage.getItem('cart') || '[]');
            cart.push({title: title, price: price});
            localStorage.setItem('cart', JSON.stringify(cart));
            alert(title + ' added to cart!');
        }
    </script>
</body>
</html>
