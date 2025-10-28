-- Insert initial user accounts
INSERT INTO users (email, password, user_level, full_name) VALUES
('pimpinan@tvri.co.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Leader', 'Pimpinan TVRI'),
('admin@tvri.co.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'Administrator TVRI'),
('teknisi@tvri.co.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Technician', 'Teknisi TVRI');

-- Note: All passwords are hashed versions of: pimpinan123, admin123, teknisi123

-- Insert staff options
INSERT INTO staff_options (name, type) VALUES
('Fikky Afrianto', 'shift_staff'),
('M. Iqbal Perdana P', 'shift_staff'),
('Randy Maskita', 'shift_staff'),
('Yastrib Kamal', 'shift_staff'),
('Aulia Adi Utama', 'shift_staff'),
('Rezky Hadi Setiawan', 'shift_staff'),
('Fikky Afrianto', 'report_staff'),
('M. Iqbal Perdana P', 'report_staff'),
('Randy Maskita', 'report_staff'),
('Yastrib Kamal', 'report_staff'),
('Aulia Adi Utama', 'report_staff'),
('Rezky Hadi Setiawan', 'report_staff');

-- Insert program options
INSERT INTO program_options (name, type) VALUES
('Klik Indonesia Pagi', 'national'),
('Bersama Perempuan', 'national'),
('Mari Menggambar', 'national'),
('Info Sehat', 'local'),
('Riau Hari Ini', 'local'),
('Dialog Riau Cemerlang', 'local');

-- Insert transmission units
INSERT INTO transmission_units (name) VALUES
('Pekanbaru'),
('Dumai'),
('Rokan Hilir'),
('Pasir Pangaraian');