-- Laboratory Deployment & Inventory System Database Schema

-- Create Database
CREATE DATABASE IF NOT EXISTS deployment_system;
USE deployment_system;

-- Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20),
    role ENUM('admin', 'manager', 'user') DEFAULT 'user',
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Categories Table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    type ENUM('equipment', 'deployment') NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Locations Table
CREATE TABLE locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address TEXT,
    contact_person VARCHAR(100),
    contact_phone VARCHAR(20),
    contact_email VARCHAR(100),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Suppliers Table
CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Inventory Items Table
CREATE TABLE inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    category_id INT,
    supplier_id INT,
    model VARCHAR(100),
    serial_number VARCHAR(100),
    purchase_date DATE,
    purchase_cost DECIMAL(10,2),
    warranty_expiry DATE,
    condition_status ENUM('excellent', 'good', 'fair', 'poor') DEFAULT 'good',
    status ENUM('available', 'deployed', 'maintenance', 'retired') DEFAULT 'available',
    location_id INT,
    assigned_to INT NULL,
    image VARCHAR(255),
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (location_id) REFERENCES locations(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Deployments Table
CREATE TABLE deployments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    deployment_code VARCHAR(50) UNIQUE NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    category_id INT,
    location_id INT,
    assigned_to INT,
    start_date DATE NOT NULL,
    end_date DATE,
    estimated_duration INT, -- in days
    status ENUM('pending', 'active', 'completed', 'cancelled') DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    budget DECIMAL(10,2),
    actual_cost DECIMAL(10,2),
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (location_id) REFERENCES locations(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Deployment Items Table (Many-to-Many relationship)
CREATE TABLE deployment_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    deployment_id INT NOT NULL,
    inventory_id INT NOT NULL,
    quantity INT DEFAULT 1,
    checkout_date DATETIME NOT NULL,
    expected_return_date DATETIME,
    actual_return_date DATETIME NULL,
    condition_checkout ENUM('excellent', 'good', 'fair', 'poor') DEFAULT 'good',
    condition_return ENUM('excellent', 'good', 'fair', 'poor') NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (deployment_id) REFERENCES deployments(id) ON DELETE CASCADE,
    FOREIGN KEY (inventory_id) REFERENCES inventory(id) ON DELETE CASCADE
);

-- Maintenance Log Table
CREATE TABLE maintenance_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inventory_id INT NOT NULL,
    maintenance_type ENUM('preventive', 'corrective', 'emergency') NOT NULL,
    description TEXT NOT NULL,
    technician VARCHAR(100),
    maintenance_date DATE NOT NULL,
    cost DECIMAL(10,2),
    next_maintenance_date DATE,
    status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled',
    performed_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (inventory_id) REFERENCES inventory(id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by) REFERENCES users(id)
);

-- Activity Log Table
CREATE TABLE activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Notifications Table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'success', 'danger') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Settings Table
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id)
);

-- Insert Default Admin User
INSERT INTO users (username, email, password, first_name, last_name, role, status) 
VALUES ('admin', 'admin@labsystem.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', 'admin', 'active');

-- Insert Default Categories
INSERT INTO categories (name, description, type) VALUES 
('Laboratory Equipment', 'Scientific and testing equipment', 'equipment'),
('Computer Hardware', 'IT and computing equipment', 'equipment'),
('Network Equipment', 'Networking and communication devices', 'equipment'),
('Deployment Units', 'Mobile deployment systems', 'equipment'),
('Field Research', 'Field research and data collection', 'deployment'),
('Site Installation', 'On-site equipment installation', 'deployment'),
('System Maintenance', 'Maintenance and support deployments', 'deployment'),
('Emergency Response', 'Emergency response deployments', 'deployment');

-- Insert Default Locations
INSERT INTO locations (name, address, contact_person, contact_phone, contact_email) VALUES 
('Main Laboratory', '123 Science Park, Metro Manila', 'John Doe', '09123456789', 'mainlab@labsystem.com'),
('Field Office A', '456 Research Ave, Quezon City', 'Jane Smith', '09234567890', 'fielda@labsystem.com'),
('Field Office B', '789 Technology St, Makati City', 'Bob Johnson', '09345678901', 'fieldb@labsystem.com'),
('Storage Facility', '321 Warehouse Rd, Pasig City', 'Alice Brown', '09456789012', 'storage@labsystem.com');

-- Insert Default Settings
INSERT INTO settings (setting_key, setting_value, description) VALUES 
('maintenance_reminder_days', '30', 'Days before maintenance due to send reminder'),
('warranty_expiry_days', '60', 'Days before warranty expiry to send notification'),
('deployment_duration_limit', '365', 'Maximum deployment duration in days'),
('auto_return_overdue', '1', 'Automatically mark overdue items for return'),
('email_notifications', '1', 'Enable email notifications'),
('system_timezone', 'Asia/Manila', 'System default timezone');

-- Create Views for commonly used data
CREATE VIEW inventory_with_details AS
SELECT 
    i.*,
    c.name as category_name,
    s.name as supplier_name,
    l.name as location_name,
    CONCAT(u1.first_name, ' ', u1.last_name) as assigned_user,
    CONCAT(u2.first_name, ' ', u2.last_name) as created_by_name
FROM inventory i
LEFT JOIN categories c ON i.category_id = c.id
LEFT JOIN suppliers s ON i.supplier_id = s.id
LEFT JOIN locations l ON i.location_id = l.id
LEFT JOIN users u1 ON i.assigned_to = u1.id
LEFT JOIN users u2 ON i.created_by = u2.id;

CREATE VIEW deployment_with_details AS
SELECT 
    d.*,
    c.name as category_name,
    l.name as location_name,
    CONCAT(u1.first_name, ' ', u1.last_name) as assigned_user,
    CONCAT(u2.first_name, ' ', u2.last_name) as created_by_name,
    COUNT(di.id) as items_count
FROM deployments d
LEFT JOIN categories c ON d.category_id = c.id
LEFT JOIN locations l ON d.location_id = l.id
LEFT JOIN users u1 ON d.assigned_to = u1.id
LEFT JOIN users u2 ON d.created_by = u2.id
LEFT JOIN deployment_items di ON d.id = di.deployment_id
GROUP BY d.id;