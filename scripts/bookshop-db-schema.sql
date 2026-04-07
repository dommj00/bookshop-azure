-- ============================================================
-- BookShop Database Rebuild Script
-- Original: Azure SQL (bookshop-server-chippy / Bookshop-DB)
-- Target: SQL Server 2022 on Ubuntu Linux (VirtualBox)
-- ============================================================

-- Create the database
IF NOT EXISTS (SELECT name FROM sys.databases WHERE name = 'BookshopDB')
BEGIN
    CREATE DATABASE BookshopDB;
END
GO

USE BookshopDB;
GO

-- ============================================================
-- TABLE 1: Users (Customer accounts)
-- ============================================================
CREATE TABLE Users (
    UserID INT IDENTITY(1,1) PRIMARY KEY,
    Username NVARCHAR(50) UNIQUE NOT NULL,
    Email NVARCHAR(100) UNIQUE NOT NULL,
    PasswordHash NVARCHAR(255) NOT NULL,
    FirstName NVARCHAR(50),
    LastName NVARCHAR(50),
    Phone NVARCHAR(20),
    Address NVARCHAR(500),
    IsActive BIT DEFAULT 1,
    CreatedDate DATETIME DEFAULT GETDATE(),
    LastLoginDate DATETIME
);
GO

-- ============================================================
-- TABLE 2: Categories
-- ============================================================
CREATE TABLE Categories (
    CategoryID INT IDENTITY(1,1) PRIMARY KEY,
    CategoryName NVARCHAR(50) NOT NULL,
    Description NVARCHAR(200)
);
GO

-- ============================================================
-- TABLE 3: Products (Book inventory)
-- ============================================================
CREATE TABLE Products (
    ProductID INT IDENTITY(1,1) PRIMARY KEY,
    Title NVARCHAR(200) NOT NULL,
    Author NVARCHAR(100),
    ISBN NVARCHAR(20),
    Description NVARCHAR(MAX),
    Price DECIMAL(10,2) NOT NULL,
    CategoryID INT,
    StockQuantity INT DEFAULT 0,
    ImageURL NVARCHAR(500),
    IsActive BIT DEFAULT 1,
    CreatedDate DATETIME DEFAULT GETDATE(),
    CONSTRAINT FK_Products_Categories FOREIGN KEY (CategoryID) REFERENCES Categories(CategoryID)
);
GO

-- ============================================================
-- TABLE 4: Orders
-- ============================================================
CREATE TABLE Orders (
    OrderID INT IDENTITY(1,1) PRIMARY KEY,
    UserID INT NOT NULL,
    OrderDate DATETIME DEFAULT GETDATE(),
    TotalAmount DECIMAL(10,2) NOT NULL,
    Status NVARCHAR(20) DEFAULT 'Pending',
    ShippingAddress NVARCHAR(500),
    CONSTRAINT FK_Orders_Users FOREIGN KEY (UserID) REFERENCES Users(UserID)
);
GO

-- ============================================================
-- TABLE 5: OrderItems
-- ============================================================
CREATE TABLE OrderItems (
    OrderItemID INT IDENTITY(1,1) PRIMARY KEY,
    OrderID INT NOT NULL,
    ProductID INT NOT NULL,
    Quantity INT NOT NULL,
    Price DECIMAL(10,2) NOT NULL,
    CONSTRAINT FK_OrderItems_Orders FOREIGN KEY (OrderID) REFERENCES Orders(OrderID),
    CONSTRAINT FK_OrderItems_Products FOREIGN KEY (ProductID) REFERENCES Products(ProductID)
);
GO

-- ============================================================
-- TABLE 6: AdminUsers
-- ============================================================
CREATE TABLE AdminUsers (
    AdminID INT IDENTITY(1,1) PRIMARY KEY,
    Username NVARCHAR(50) UNIQUE NOT NULL,
    PasswordHash NVARCHAR(255) NOT NULL,
    Email NVARCHAR(100),
    Role NVARCHAR(20) DEFAULT 'admin',
    IsActive BIT DEFAULT 1,
    CreatedDate DATETIME DEFAULT GETDATE(),
    LastLoginDate DATETIME
);
GO

-- ============================================================
-- TABLE 7: UserSessions
-- ============================================================
CREATE TABLE UserSessions (
    SessionID INT IDENTITY(1,1) PRIMARY KEY,
    UserID INT,
    AdminID INT,
    SessionToken NVARCHAR(255) NOT NULL,
    IPAddress NVARCHAR(45),
    UserAgent NVARCHAR(500),
    CreatedDate DATETIME DEFAULT GETDATE(),
    ExpiresDate DATETIME,
    IsActive BIT DEFAULT 1,
    CONSTRAINT FK_Sessions_Users FOREIGN KEY (UserID) REFERENCES Users(UserID),
    CONSTRAINT FK_Sessions_Admins FOREIGN KEY (AdminID) REFERENCES AdminUsers(AdminID)
);
GO

-- ============================================================
-- TABLE 8: AuditLogs
-- ============================================================
CREATE TABLE AuditLogs (
    LogID INT IDENTITY(1,1) PRIMARY KEY,
    UserID INT,
    AdminID INT,
    Action NVARCHAR(100) NOT NULL,
    TableAffected NVARCHAR(50),
    RecordID INT,
    OldValue NVARCHAR(MAX),
    NewValue NVARCHAR(MAX),
    IPAddress NVARCHAR(45),
    Timestamp DATETIME DEFAULT GETDATE()
);
GO

-- ============================================================
-- TABLE 9: PaymentMethods
-- ============================================================
CREATE TABLE PaymentMethods (
    PaymentMethodID INT IDENTITY(1,1) PRIMARY KEY,
    UserID INT NOT NULL,
    CardType NVARCHAR(20) NOT NULL,
    LastFourDigits NVARCHAR(4) NOT NULL,
    ExpiryMonth INT NOT NULL,
    ExpiryYear INT NOT NULL,
    CardholderName NVARCHAR(100) NOT NULL,
    IsDefault BIT DEFAULT 0,
    CreatedDate DATETIME DEFAULT GETDATE(),
    CONSTRAINT FK_Payment_Users FOREIGN KEY (UserID) REFERENCES Users(UserID)
);
GO

-- ============================================================
-- TABLE 10: Subscriptions
-- ============================================================
CREATE TABLE Subscriptions (
    SubscriptionID INT IDENTITY(1,1) PRIMARY KEY,
    PlanName NVARCHAR(50) NOT NULL,
    Description NVARCHAR(500),
    MonthlyPrice DECIMAL(10,2) NOT NULL,
    BooksPerMonth INT DEFAULT 1,
    IsActive BIT DEFAULT 1,
    CreatedDate DATETIME DEFAULT GETDATE()
);
GO

-- ============================================================
-- TABLE 11: UserSubscriptions
-- ============================================================
CREATE TABLE UserSubscriptions (
    UserSubscriptionID INT IDENTITY(1,1) PRIMARY KEY,
    UserID INT NOT NULL,
    SubscriptionID INT NOT NULL,
    PaymentMethodID INT,
    Status NVARCHAR(20) DEFAULT 'Active',
    StartDate DATETIME DEFAULT GETDATE(),
    NextBillingDate DATETIME,
    CancelledDate DATETIME,
    CONSTRAINT FK_UserSub_Users FOREIGN KEY (UserID) REFERENCES Users(UserID),
    CONSTRAINT FK_UserSub_Subs FOREIGN KEY (SubscriptionID) REFERENCES Subscriptions(SubscriptionID),
    CONSTRAINT FK_UserSub_Payment FOREIGN KEY (PaymentMethodID) REFERENCES PaymentMethods(PaymentMethodID)
);
GO


-- ============================================================
-- SAMPLE DATA
-- ============================================================

-- Categories (4 categories)
INSERT INTO Categories (CategoryName, Description) VALUES
('Fiction', 'Fiction books including novels and short stories'),
('Nonfiction', 'Educational and informational books'),
('Educational', 'Textbooks and learning materials'),
('Self-Help', 'Personal development and inspirational books');
GO

-- Products (17 books across 4 categories)
-- Categories: 1=Fiction, 2=Nonfiction, 3=Educational, 4=Self-Help
INSERT INTO Products (Title, Author, ISBN, Description, Price, CategoryID, StockQuantity, ImageURL) VALUES
('Breaking Barriers', 'Carlos Mendes', '9781234567890', 'A powerful story about overcoming obstacles and pushing past limits', 24.99, 4, 15, '/images/breaking-barriers.jpg'),
('Climate Action Now', 'Dr. Rachel Green', '9781234567891', 'An urgent look at climate change and what we can do about it', 29.99, 2, 8, '/images/climate-action.jpg'),
('The Coffee Shop Chronicles', 'Emma Thompson', '9781234567892', 'Interconnected stories of lives crossing paths at a neighborhood cafe', 19.99, 1, 12, '/images/coffee-shop.jpg'),
('Crimson Tides', 'Robert Blake', '9781234567893', 'A gripping thriller set against the backdrop of the open sea', 22.99, 1, 10, '/images/crimson-tides.jpg'),
('The Digital Revolution', 'Dr. Alan Kumar', '9781234567894', 'How technology is reshaping society, business, and everyday life', 34.99, 2, 20, '/images/digital-revolution.jpg'),
('Echoes of Tomorrow', 'James Chen', '9781234567895', 'A science fiction novel exploring the consequences of time manipulation', 21.99, 1, 9, '/images/echoes-tomorrow.jpg'),
('The Economics of Everything', 'David Miller', '9781234567896', 'Understanding economic principles through everyday decisions', 32.99, 2, 14, '/images/economics-everything.jpg'),
('The Innovator''s Mind', 'Lisa Parker', '9781234567897', 'Inside the thinking patterns of the world''s most creative problem solvers', 27.99, 2, 11, '/images/innovators-mind.jpg'),
('Introduction to Psychology', 'Dr. Michael Brown', '9781234567898', 'A comprehensive introduction to the study of human behavior and mind', 49.99, 3, 25, '/images/intro-psychology.jpg'),
('Journey to the Stars', 'Mike Harris', '9781234567899', 'An adventure through the cosmos blending science and imagination', 23.99, 1, 13, '/images/journey-stars.jpg'),
('The Last Bookkeeper', 'Maria Rodriguez', '9781234567900', 'A mystery surrounding the guardian of an ancient library''s secrets', 20.99, 1, 7, '/images/last-bookkeeper.jpg'),
('Mathematics Made Simple', 'Linda Johnson', '9781234567901', 'Making math accessible and approachable for all learners', 39.99, 3, 18, '/images/math-simple.jpg'),
('The Midnight Garden', 'Sarah Mitchell', '9781234567902', 'A enchanting tale of a hidden garden that only appears at night', 18.99, 1, 16, '/images/midnight-garden.jpg'),
('Mindful Living', 'Jennifer Walsh', '9781234567903', 'Practical strategies for living with intention and awareness', 25.99, 4, 22, '/images/mindful-living.jpg'),
('From Poverty to Purpose', 'Grace Okonkwo', '9781234567904', 'An inspiring journey from hardship to finding meaning and impact', 26.99, 4, 9, '/images/poverty-purpose.jpg'),
('Programming Fundamentals', 'Alex Morgan', '9781234567905', 'Essential programming concepts and practices for beginners', 44.99, 3, 19, '/images/programming-fundamentals.jpg'),
('World History: A Complete Guide', 'Susan Davis', '9781234567906', 'A thorough exploration of civilizations, conflicts, and cultural milestones', 54.99, 3, 12, '/images/world-history.jpg');
GO

-- Test Users (3 demo users - intentionally weak passwords for security testing)
-- NOTE: Passwords stored as plain text - this is an INTENTIONAL vulnerability
INSERT INTO Users (Username, Email, PasswordHash, FirstName, LastName, Phone, Address) VALUES
('testuser1', 'testuser1@bookshop.com', 'password123', 'Alice', 'Johnson', '555-0101', '123 Main St, Springfield, IL 62701'),
('testuser2', 'testuser2@bookshop.com', 'password456', 'Bob', 'Williams', '555-0102', '456 Oak Ave, Portland, OR 97201'),
('demouser', 'demo@bookshop.com', 'demo2024', 'Charlie', 'Davis', '555-0103', '789 Pine Rd, Austin, TX 78701');
GO

-- Admin Accounts (intentionally weak credentials for security testing)
INSERT INTO AdminUsers (Username, PasswordHash, Email, Role) VALUES
('admin', 'admin123', 'admin@bookshop.com', 'superadmin'),
('manager', 'password', 'manager@bookshop.com', 'admin');
GO

-- Subscription Plans (3 tiers)
INSERT INTO Subscriptions (PlanName, Description, MonthlyPrice, BooksPerMonth) VALUES
('Basic', 'One book per month with free shipping', 9.99, 1),
('Premium', 'Three books per month with priority shipping', 24.99, 3),
('Enterprise', 'Unlimited books with express shipping and exclusive titles', 49.99, 10);
GO

-- Payment Methods (fake test data)
INSERT INTO PaymentMethods (UserID, CardType, LastFourDigits, ExpiryMonth, ExpiryYear, CardholderName, IsDefault) VALUES
(1, 'Visa', '4242', 12, 2026, 'Alice Johnson', 1),
(1, 'Mastercard', '8888', 6, 2027, 'Alice Johnson', 0),
(2, 'Visa', '1234', 3, 2026, 'Bob Williams', 1),
(3, 'Amex', '5678', 9, 2027, 'Charlie Davis', 1);
GO

-- Sample Orders
INSERT INTO Orders (UserID, TotalAmount, Status, ShippingAddress) VALUES
(1, 47.98, 'Delivered', '123 Main St, Springfield, IL 62701'),
(1, 34.99, 'Shipped', '123 Main St, Springfield, IL 62701'),
(2, 94.97, 'Processing', '456 Oak Ave, Portland, OR 97201'),
(3, 25.99, 'Pending', '789 Pine Rd, Austin, TX 78701');
GO

-- Sample Order Items
INSERT INTO OrderItems (OrderID, ProductID, Quantity, Price) VALUES
(1, 1, 1, 24.99),
(1, 2, 1, 19.99),
(1, 3, 1, 22.99),  -- extra item intentional for testing
(2, 7, 1, 34.99),
(3, 11, 1, 49.99),
(3, 13, 1, 44.99),
(4, 6, 1, 25.99);
GO

-- Sample User Subscriptions
INSERT INTO UserSubscriptions (UserID, SubscriptionID, PaymentMethodID, Status, NextBillingDate) VALUES
(1, 2, 1, 'Active', DATEADD(MONTH, 1, GETDATE())),
(2, 1, 3, 'Active', DATEADD(MONTH, 1, GETDATE())),
(3, 3, 4, 'Cancelled', NULL);
GO

-- Sample Audit Log entries
INSERT INTO AuditLogs (UserID, AdminID, Action, TableAffected, RecordID, IPAddress) VALUES
(NULL, 1, 'LOGIN', 'AdminUsers', 1, '192.168.1.100'),
(NULL, 1, 'ADD_PRODUCT', 'Products', 17, '192.168.1.100'),
(1, NULL, 'LOGIN', 'Users', 1, '203.0.113.50'),
(1, NULL, 'PLACE_ORDER', 'Orders', 1, '203.0.113.50'),
(2, NULL, 'LOGIN', 'Users', 2, '198.51.100.25'),
(NULL, 2, 'LOGIN', 'AdminUsers', 2, '192.168.1.101'),
(3, NULL, 'REGISTER', 'Users', 3, '192.0.2.75'),
(NULL, 1, 'UPDATE_ORDER_STATUS', 'Orders', 1, '192.168.1.100');
GO

-- Sample Session entries
INSERT INTO UserSessions (UserID, AdminID, SessionToken, IPAddress, UserAgent, ExpiresDate, IsActive) VALUES
(1, NULL, 'abc123def456ghi789', '203.0.113.50', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', DATEADD(HOUR, 24, GETDATE()), 1),
(2, NULL, 'xyz789mno456pqr123', '198.51.100.25', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)', DATEADD(HOUR, 24, GETDATE()), 1),
(NULL, 1, 'admin-session-token-001', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', DATEADD(HOUR, 8, GETDATE()), 1);
GO

-- ============================================================
-- VERIFICATION QUERIES
-- ============================================================
PRINT '=== Database Rebuild Complete ==='
PRINT ''

SELECT 'Users' AS TableName, COUNT(*) AS RecordCount FROM Users
UNION ALL SELECT 'Categories', COUNT(*) FROM Categories
UNION ALL SELECT 'Products', COUNT(*) FROM Products
UNION ALL SELECT 'Orders', COUNT(*) FROM Orders
UNION ALL SELECT 'OrderItems', COUNT(*) FROM OrderItems
UNION ALL SELECT 'AdminUsers', COUNT(*) FROM AdminUsers
UNION ALL SELECT 'UserSessions', COUNT(*) FROM UserSessions
UNION ALL SELECT 'AuditLogs', COUNT(*) FROM AuditLogs
UNION ALL SELECT 'PaymentMethods', COUNT(*) FROM PaymentMethods
UNION ALL SELECT 'Subscriptions', COUNT(*) FROM Subscriptions
UNION ALL SELECT 'UserSubscriptions', COUNT(*) FROM UserSubscriptions;
GO

PRINT 'Expected: Users=3, Categories=4, Products=17, Orders=4, OrderItems=7'
PRINT 'Expected: AdminUsers=2, UserSessions=3, AuditLogs=8, PaymentMethods=4'
PRINT 'Expected: Subscriptions=3, UserSubscriptions=3'
GO
