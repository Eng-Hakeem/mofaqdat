CREATE DATABASE IF NOT EXISTS mofaqdat CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mofaqdat;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    avatar VARCHAR(255) DEFAULT 'default.png',
    is_verified TINYINT(1) DEFAULT 0,
    id_verified TINYINT(1) DEFAULT 0,
    role ENUM('user', 'admin') DEFAULT 'user',
    verification_token VARCHAR(64),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    name_ar VARCHAR(100) NOT NULL,
    icon VARCHAR(50),
    description TEXT
);

CREATE TABLE reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    report_type ENUM('lost', 'found') NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    location_name VARCHAR(200),
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    date_occurred DATE,
    time_occurred TIME,
    image VARCHAR(255),
    additional_images TEXT,
    distinguishing_marks TEXT,
    color VARCHAR(50),
    brand VARCHAR(100),
    model VARCHAR(100),
    status ENUM('active', 'matched', 'resolved', 'expired') DEFAULT 'active',
    privacy_level ENUM('full', 'limited', 'hidden') DEFAULT 'limited',
    contact_method ENUM('phone', 'email', 'both') DEFAULT 'both',
    reward_amount DECIMAL(10,2) DEFAULT 0,
    views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

CREATE TABLE matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lost_report_id INT NOT NULL,
    found_report_id INT NOT NULL,
    match_score DECIMAL(5,2) DEFAULT 0,
    match_reason TEXT,
    status ENUM('suggested', 'confirmed', 'rejected') DEFAULT 'suggested',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lost_report_id) REFERENCES reports(id) ON DELETE CASCADE,
    FOREIGN KEY (found_report_id) REFERENCES reports(id) ON DELETE CASCADE
);

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    report_id INT,
    matched_report_id INT,
    match_id INT,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('match', 'new_found', 'status', 'system') DEFAULT 'system',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE SET NULL,
    FOREIGN KEY (matched_report_id) REFERENCES reports(id) ON DELETE SET NULL,
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE SET NULL
);

CREATE TABLE verification_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    report_id INT NOT NULL,
    document_type ENUM('national_id', 'passport', 'other') NOT NULL,
    document_image VARCHAR(255) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE
);

INSERT INTO categories (name, name_ar, icon, description) VALUES
('persons', 'الأشخاص', 'fa-user', 'الأشخاص المفقودون أو المعثور عليهم'),
('documents', 'الوثائق والمستندات', 'fa-id-card', 'هوية، جواز سفر، رخصة، شهادات'),
('phones', 'الهواتف الإلكترونية', 'fa-mobile-alt', 'هواتف ذكية وتابلت'),
('electronics', 'الإلكترونيات', 'fa-laptop', 'أجهزة كمبيوتر، كاميرات، سماعات'),
('jewelry', 'المجوهرات', 'fa-gem', 'خواتم، سلاسل، ساعات، أقراط'),
('bags', 'حقائب ومحافظ', 'fa-shopping-bag', 'حقائب يد، محافظ، حقائب ظهر'),
('vehicles', 'المركبات والدراجات', 'fa-car', 'سيارات، دراجات هوائية، دراجات نارية'),
('keys', 'المفاتيح', 'fa-key', 'مفاتيح سيارة، مفاتيح منزل'),
('pets', 'الحيوانات الأليفة', 'fa-paw', 'قطط، كلاب، طيور'),
('other', 'أخرى', 'fa-ellipsis-h', 'أي أشياء أخرى غير مدرجة');
