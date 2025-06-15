-- MySQL Database Structure for Romanian Products E-commerce Platform
-- Created for Gusturi Românești

-- Drop database if exists (for development purposes)
DROP DATABASE IF EXISTS gusturi_romanesti;

-- Create database with proper character set
CREATE DATABASE gusturi_romanesti CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Use the database
USE gusturi_romanesti;

-- Table: utilizatori (users)
CREATE TABLE utilizatori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    parola VARCHAR(255) NOT NULL, -- Hashed password
    nume VARCHAR(50) NOT NULL,
    prenume VARCHAR(50) NOT NULL,
    telefon VARCHAR(15),
    adresa TEXT,
    oras VARCHAR(50),
    judet VARCHAR(50),
    cod_postal VARCHAR(10),
    rol ENUM('Client', 'Administrator') NOT NULL DEFAULT 'Client',
    data_inregistrare TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultima_autentificare TIMESTAMP NULL,
    activ BOOLEAN DEFAULT TRUE,
    token_resetare_parola VARCHAR(100) NULL,
    data_expirare_token TIMESTAMP NULL
) ENGINE=InnoDB;

-- Table: categorii (categories)
CREATE TABLE categorii (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nume VARCHAR(50) NOT NULL UNIQUE,
    descriere TEXT,
    slug VARCHAR(50) NOT NULL UNIQUE,
    imagine VARCHAR(255),
    activ BOOLEAN DEFAULT TRUE,
    ordine INT DEFAULT 0
) ENGINE=InnoDB;

-- Table: regiuni (regions)
CREATE TABLE regiuni (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nume VARCHAR(50) NOT NULL UNIQUE,
    descriere TEXT,
    imagine VARCHAR(255)
) ENGINE=InnoDB;

-- Table: produse (products)
CREATE TABLE produse (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cod_produs VARCHAR(20) NOT NULL UNIQUE,
    denumire VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    descriere TEXT,
    descriere_scurta VARCHAR(255),
    pret DECIMAL(10, 2) NOT NULL,
    pret_redus DECIMAL(10, 2) NULL,
    greutate VARCHAR(20) NOT NULL,
    stoc INT NOT NULL DEFAULT 0,
    id_categorie INT NOT NULL,
    id_regiune INT NOT NULL,
    imagine VARCHAR(255),
    data_adaugare TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_actualizare TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    data_expirare DATE NULL,
    recomandat BOOLEAN DEFAULT FALSE,
    activ BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (id_categorie) REFERENCES categorii(id) ON DELETE RESTRICT,
    FOREIGN KEY (id_regiune) REFERENCES regiuni(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Table: etichete_produse (product tags)
CREATE TABLE etichete (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nume VARCHAR(50) NOT NULL UNIQUE,
    descriere VARCHAR(255)
) ENGINE=InnoDB;

-- Table: produse_etichete (product-tag relationship)
CREATE TABLE produse_etichete (
    id_produs INT NOT NULL,
    id_eticheta INT NOT NULL,
    PRIMARY KEY (id_produs, id_eticheta),
    FOREIGN KEY (id_produs) REFERENCES produse(id) ON DELETE CASCADE,
    FOREIGN KEY (id_eticheta) REFERENCES etichete(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table: informatii_nutritionale (nutritional information)
CREATE TABLE informatii_nutritionale (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_produs INT NOT NULL UNIQUE,
    valoare_energetica VARCHAR(50),
    grasimi DECIMAL(5, 2),
    grasimi_saturate DECIMAL(5, 2),
    glucide DECIMAL(5, 2),
    zaharuri DECIMAL(5, 2),
    fibre DECIMAL(5, 2),
    proteine DECIMAL(5, 2),
    sare DECIMAL(5, 2),
    ingrediente TEXT,
    alergeni TEXT,
    FOREIGN KEY (id_produs) REFERENCES produse(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table: imagini_produse (product images)
CREATE TABLE imagini_produse (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_produs INT NOT NULL,
    url VARCHAR(255) NOT NULL,
    ordine INT DEFAULT 0,
    FOREIGN KEY (id_produs) REFERENCES produse(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table: cos_cumparaturi (shopping cart)
CREATE TABLE cos_cumparaturi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_utilizator INT NOT NULL,
    id_produs INT NOT NULL,
    cantitate INT NOT NULL DEFAULT 1,
    data_adaugare TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_utilizator) REFERENCES utilizatori(id) ON DELETE CASCADE,
    FOREIGN KEY (id_produs) REFERENCES produse(id) ON DELETE CASCADE,
    UNIQUE KEY (id_utilizator, id_produs)
) ENGINE=InnoDB;

-- Table: favorite (wishlist)
CREATE TABLE favorite (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_utilizator INT NOT NULL,
    id_produs INT NOT NULL,
    data_adaugare TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_utilizator) REFERENCES utilizatori(id) ON DELETE CASCADE,
    FOREIGN KEY (id_produs) REFERENCES produse(id) ON DELETE CASCADE,
    UNIQUE KEY (id_utilizator, id_produs)
) ENGINE=InnoDB;

-- Table: vouchere (vouchers)
CREATE TABLE vouchere (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cod VARCHAR(20) NOT NULL UNIQUE,
    tip ENUM('Procent', 'Valoare') NOT NULL,
    valoare DECIMAL(10, 2) NOT NULL,
    data_inceput DATE NOT NULL,
    data_expirare DATE NOT NULL,
    utilizari_maxime INT NULL,
    utilizari_curente INT DEFAULT 0,
    valoare_minima_comanda DECIMAL(10, 2) DEFAULT 0,
    activ BOOLEAN DEFAULT TRUE,
    descriere VARCHAR(255)
) ENGINE=InnoDB;

-- Table: comenzi (orders)
CREATE TABLE comenzi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numar_comanda VARCHAR(20) NOT NULL UNIQUE,
    id_utilizator INT NOT NULL,
    status ENUM('Plasată', 'Confirmată', 'În procesare', 'Expediată', 'Livrată', 'Anulată') NOT NULL DEFAULT 'Plasată',
    metoda_plata ENUM('Card', 'Transfer bancar', 'Ramburs') NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    cost_transport DECIMAL(10, 2) NOT NULL,
    id_voucher INT NULL,
    valoare_reducere DECIMAL(10, 2) DEFAULT 0,
    total DECIMAL(10, 2) NOT NULL,
    adresa_livrare TEXT NOT NULL,
    oras_livrare VARCHAR(50) NOT NULL,
    judet_livrare VARCHAR(50) NOT NULL,
    cod_postal_livrare VARCHAR(10) NOT NULL,
    telefon_livrare VARCHAR(15) NOT NULL,
    email_livrare VARCHAR(100) NOT NULL,
    nume_livrare VARCHAR(100) NOT NULL,
    observatii TEXT,
    data_plasare TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_confirmare TIMESTAMP NULL,
    data_expediere TIMESTAMP NULL,
    data_livrare TIMESTAMP NULL,
    numar_awb VARCHAR(50),
    FOREIGN KEY (id_utilizator) REFERENCES utilizatori(id) ON DELETE RESTRICT,
    FOREIGN KEY (id_voucher) REFERENCES vouchere(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Table: comenzi_produse (order items)
CREATE TABLE comenzi_produse (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_comanda INT NOT NULL,
    id_produs INT NOT NULL,
    denumire_produs VARCHAR(100) NOT NULL,
    pret_unitar DECIMAL(10, 2) NOT NULL,
    cantitate INT NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (id_comanda) REFERENCES comenzi(id) ON DELETE CASCADE,
    FOREIGN KEY (id_produs) REFERENCES produse(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Table: puncte_fidelitate (loyalty points)
CREATE TABLE puncte_fidelitate (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_utilizator INT NOT NULL,
    puncte_totale INT NOT NULL DEFAULT 0,
    puncte_folosite INT NOT NULL DEFAULT 0,
    data_actualizare TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_utilizator) REFERENCES utilizatori(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table: tranzactii_puncte (loyalty points transactions)
CREATE TABLE tranzactii_puncte (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_utilizator INT NOT NULL,
    id_comanda INT NULL,
    puncte INT NOT NULL, -- Positive for earned, negative for spent
    descriere VARCHAR(255) NOT NULL,
    data_tranzactie TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_utilizator) REFERENCES utilizatori(id) ON DELETE CASCADE,
    FOREIGN KEY (id_comanda) REFERENCES comenzi(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Table: jurnalizare (logging)
CREATE TABLE jurnalizare (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_utilizator INT NULL,
    actiune VARCHAR(255) NOT NULL,
    detalii TEXT,
    ip VARCHAR(45),
    user_agent TEXT,
    data_actiune TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_utilizator) REFERENCES utilizatori(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Table: recenzii (reviews)
CREATE TABLE recenzii (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_produs INT NOT NULL,
    id_utilizator INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    titlu VARCHAR(100),
    comentariu TEXT,
    data_adaugare TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    aprobat BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (id_produs) REFERENCES produse(id) ON DELETE CASCADE,
    FOREIGN KEY (id_utilizator) REFERENCES utilizatori(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table: newsletter_abonati (newsletter subscribers)
CREATE TABLE newsletter_abonati (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    nume VARCHAR(100),
    data_abonare TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activ BOOLEAN DEFAULT TRUE,
    token_dezabonare VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

-- Table: contacte (contact form submissions)
CREATE TABLE contacte (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nume VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    telefon VARCHAR(15),
    subiect VARCHAR(100) NOT NULL,
    mesaj TEXT NOT NULL,
    data_trimitere TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    rezolvat BOOLEAN DEFAULT FALSE
) ENGINE=InnoDB;

-- Table: istoric_preturi (price history)
CREATE TABLE istoric_preturi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_produs INT NOT NULL,
    pret_vechi DECIMAL(10, 2) NOT NULL,
    pret_nou DECIMAL(10, 2) NOT NULL,
    data_modificare TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    id_utilizator INT NULL, -- Admin who changed the price
    FOREIGN KEY (id_produs) REFERENCES produse(id) ON DELETE CASCADE,
    FOREIGN KEY (id_utilizator) REFERENCES utilizatori(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Table: setari_site (site settings)
CREATE TABLE setari_site (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cheie VARCHAR(50) NOT NULL UNIQUE,
    valoare TEXT NOT NULL,
    descriere VARCHAR(255),
    data_actualizare TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insert sample data

-- Insert categories
INSERT INTO categorii (nume, descriere, slug, imagine, ordine) VALUES
('Dulcețuri & Miere', 'Gemuri, dulcețuri și miere naturală din regiunile Carpaților', 'dulceturi', 'categories/dulceturi.jpg', 1),
('Brânzeturi', 'Brânzeturi tradiționale de vacă, oaie și capră', 'branza', 'categories/branza.jpg', 2),
('Mezeluri', 'Cârnați, slănină și specialități afumate', 'mezeluri', 'categories/mezeluri.jpg', 3),
('Băuturi', 'Țuică, pălincă, vinuri și siropuri naturale', 'bauturi', 'categories/bauturi.jpg', 4),
('Conserve & Murături', 'Zacuscă, murături și alte conserve tradiționale', 'conserve', 'categories/conserve.jpg', 5);

-- Insert regions
INSERT INTO regiuni (nume, descriere, imagine) VALUES
('Transilvania', 'Regiune istorică în centrul și nord-vestul României', 'regions/transilvania.jpg'),
('Muntenia', 'Regiune istorică în sudul României', 'regions/muntenia.jpg'),
('Maramureș', 'Regiune istorică în nordul României', 'regions/maramures.jpg'),
('Banat', 'Regiune istorică în vestul României', 'regions/banat.jpg'),
('Oltenia', 'Regiune istorică în sud-vestul României', 'regions/oltenia.jpg'),
('Dobrogea', 'Regiune istorică în sud-estul României', 'regions/dobrogea.jpg'),
('Crișana', 'Regiune istorică în vestul României', 'regions/crisana.jpg'),
('Bucovina', 'Regiune istorică în nordul României', 'regions/bucovina.jpg');

-- Insert tags
INSERT INTO etichete (nume, descriere) VALUES
('produs-de-post', 'Produse care pot fi consumate în perioadele de post'),
('fara-zahar', 'Produse fără zahăr adăugat'),
('artizanal', 'Produse realizate prin metode tradiționale, artizanale'),
('fara-aditivi', 'Produse fără aditivi sau conservanți artificiali'),
('ambalat-manual', 'Produse ambalate manual');

-- Insert sample users
INSERT INTO utilizatori (email, parola, nume, prenume, telefon, adresa, oras, judet, cod_postal, rol) VALUES
('admin@gusturi-romanesti.ro', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'Sistem', '0721234567', 'Strada Gusturilor 25', 'București', 'București', '010101', 'Administrator'),
('maria.popescu@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Popescu', 'Maria', '0722123456', 'Strada Florilor 10', 'Cluj-Napoca', 'Cluj', '400000', 'Client'),
('ion.ionescu@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ionescu', 'Ion', '0733123456', 'Bulevardul Unirii 15', 'Iași', 'Iași', '700000', 'Client');

-- Insert sample products (10 examples)
INSERT INTO produse (cod_produs, denumire, slug, descriere, descriere_scurta, pret, greutate, stoc, id_categorie, id_regiune, imagine, recomandat) VALUES
('DUL-001', 'Dulceață de Căpșuni de Argeș', 'dulceata-capsuni-arges', 'Dulceață tradițională din căpșuni proaspete cultivate în dealurile pitorești ale Argeșului. Preparată după rețete străvechi, fără conservanți artificiali, păstrând gustul autentic al căpșunilor de vară.', 'Dulceață tradițională din căpșuni proaspete de Argeș', 18.99, '350g', 50, 1, 2, 'products/dulceata-capsuni.jpg', TRUE),
('CON-001', 'Zacuscă de Buzău', 'zacusca-buzau', 'Zacuscă tradițională preparată din vinete și ardei copți pe foc de lemne, după rețeta autentică din zona Buzăului. Un produs 100% natural, fără conservanți artificiali, care păstrează gustul autentic al legumelor de vară.', 'Zacuscă tradițională cu vinete și ardei copți', 15.50, '450g', 75, 5, 2, 'products/zacusca-buzau.jpg', TRUE),
('BRZ-001', 'Brânză de Burduf', 'branza-burduf-maramures', 'Brânză tradițională de oaie maturată în burduf de brad, preparată după rețete străvechi din Maramureș. Un produs autentic cu gust intens și aromat, specific zonei montane.', 'Brânză tradițională de oaie maturată în burduf', 32.00, '500g', 30, 2, 3, 'products/branza-burduf.jpg', TRUE),
('BAU-001', 'Țuică de Prune Hunedoara', 'tuica-prune-hunedoara', 'Țuică tradițională de prune din Hunedoara, distilată după rețete străvechi transmise din generație în generație. Cu o concentrație de 52% alcool, această țuică oferă un gust autentic și o aromă intensă specifică prunelor de Transilvania.', 'Țuică tradițională de prune, 52% alcool', 45.00, '500ml', 40, 4, 1, 'products/tuica-prune.jpg', FALSE),
('DUL-002', 'Miere de Salcâm', 'miere-salcam-transilvania', 'Miere pură de salcâm din Munții Apuseni, Transilvania. Această miere cristalizată natural are un gust delicat și o aromă florală specifică, fiind considerată una dintre cele mai fine soiuri de miere din România.', 'Miere pură de salcâm din Munții Apuseni', 28.50, '500g', 60, 1, 1, 'products/miere-salcam.jpg', TRUE),
('MEZ-001', 'Cârnați de Pleșcoi', 'carnati-plescoi-muntenia', 'Cârnați afumați tradițional din Pleșcoi, Muntenia, preparați după rețete străvechi transmise din generație în generație. Acești cârnați sunt afumați cu lemn de fag și au un gust intens și aromat specific zonei.', 'Cârnați afumați tradițional din Pleșcoi', 24.99, '400g', 35, 3, 2, 'products/carnati-plescoi.jpg', FALSE),
('BRZ-002', 'Telemea de Ibănești', 'telemea-ibanesti-muntenia', 'Telemea tradițională din lapte de oaie de la Ibănești, Muntenia. Această brânză sărată are un gust intens și o textură cremoasă, fiind preparată după rețete străvechi transmise din generație în generație.', 'Telemea tradițională din lapte de oaie', 19.50, '300g', 45, 2, 2, 'products/telemea-ibanesti.jpg', FALSE),
('BAU-002', 'Pălincă de Pere Maramureș', 'palinca-pere-maramures', 'Pălincă dublă distilare din pere Williams din Maramureș. Această băutură tradițională cu 65% alcool este produsă în cantități limitate folosind doar pere selectate din livezile maramureșene, distilată după rețete străvechi.', 'Pălincă dublă distilare din pere Williams', 55.00, '500ml', 25, 4, 3, 'products/palinca-pere.jpg', TRUE),
('DUL-003', 'Gem de Caise Banat', 'gem-caise-banat', 'Gem tradițional din caise de Banat, preparat după rețete străvechi transmise din generație în generație. Acest gem păstrează bucățile de caise și are un gust intens și aromat specific fructelor coapte la soare din Banat.', 'Gem tradițional din caise de Banat', 21.50, '350g', 55, 1, 4, 'products/gem-caise.jpg', FALSE),
('MEZ-002', 'Slănină Afumată Oltenia', 'slanina-afumata-oltenia', 'Slănină afumată tradițional cu lemn de fag din Oltenia. Această slănină este preparată după rețete străvechi, fiind afumată natural timp de 72 de ore pentru a obține gustul și aroma specifică produselor tradiționale oltenești.', 'Slănină afumată tradițional cu lemn de fag', 35.00, '600g', 30, 3, 5, 'products/slanina-afumata.jpg', FALSE);

-- Insert product tags
INSERT INTO produse_etichete (id_produs, id_eticheta) VALUES
(1, 3), (1, 4), -- Dulceață de Căpșuni: artizanal, fara-aditivi
(2, 1), (2, 3), (2, 4), -- Zacuscă: produs-de-post, artizanal, fara-aditivi
(3, 3), (3, 5), -- Brânză de Burduf: artizanal, ambalat-manual
(4, 3), (4, 4), -- Țuică: artizanal, fara-aditivi
(5, 3), (5, 4), -- Miere: artizanal, fara-aditivi
(6, 3), (6, 5), -- Cârnați: artizanal, ambalat-manual
(7, 3), (7, 4), -- Telemea: artizanal, fara-aditivi
(8, 3), (8, 4), -- Pălincă: artizanal, fara-aditivi
(9, 3), (9, 4), -- Gem: artizanal, fara-aditivi
(10, 3), (10, 5); -- Slănină: artizanal, ambalat-manual

-- Insert nutritional information
INSERT INTO informatii_nutritionale (id_produs, valoare_energetica, grasimi, grasimi_saturate, glucide, zaharuri, fibre, proteine, sare, ingrediente) VALUES
(1, '245 kcal / 1025 kJ', 0.2, 0.1, 60.0, 58.0, 1.2, 0.4, 0.02, 'Căpșuni (65%), zahăr, acid citric, pectină naturală'),
(2, '85 kcal / 356 kJ', 4.2, 0.6, 8.5, 6.2, 3.8, 1.8, 1.2, 'Vinete (45%), ardei roșii (25%), ceapă, ulei de floarea-soarelui, pastă de tomate, sare, piper negru'),
(3, '298 kcal / 1247 kJ', 24.5, 16.8, 1.2, 1.2, 0.0, 18.5, 2.8, 'Lapte de oaie pasteurizat, sare, culturi de fermentare lactică, cheag'),
(4, '235 kcal / 983 kJ', 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 'Prune fermentate natural, apă de izvor'),
(5, '304 kcal / 1272 kJ', 0.0, 0.0, 82.4, 82.1, 0.0, 0.3, 0.004, 'Miere pură de salcâm 100%');

-- Insert sample vouchers
INSERT INTO vouchere (cod, tip, valoare, data_inceput, data_expirare, utilizari_maxime, valoare_minima_comanda, descriere) VALUES
('BINE10', 'Procent', 10.00, '2024-01-01', '2024-12-31', 100, 100.00, 'Voucher de bun venit - 10% reducere'),
('VARA2024', 'Procent', 15.00, '2024-06-01', '2024-08-31', 50, 150.00, 'Voucher de vară - 15% reducere'),
('LIVRARE0', 'Valoare', 15.00, '2024-01-01', '2024-12-31', 200, 200.00, 'Transport gratuit');

-- Insert sample orders
INSERT INTO comenzi (numar_comanda, id_utilizator, status, metoda_plata, subtotal, cost_transport, valoare_reducere, total, adresa_livrare, oras_livrare, judet_livrare, cod_postal_livrare, telefon_livrare, email_livrare, nume_livrare) VALUES
('ORD-2024-001', 2, 'Livrată', 'Card', 150.00, 0.00, 15.00, 135.00, 'Strada Florilor 10', 'Cluj-Napoca', 'Cluj', '400000', '0722123456', 'maria.popescu@example.com', 'Maria Popescu'),
('ORD-2024-002', 3, 'Expediată', 'Ramburs', 85.50, 15.00, 0.00, 100.50, 'Bulevardul Unirii 15', 'Iași', 'Iași', '700000', '0733123456', 'ion.ionescu@example.com', 'Ion Ionescu'),
('ORD-2024-003', 2, 'Confirmată', 'Transfer bancar', 210.00, 0.00, 0.00, 210.00, 'Strada Florilor 10', 'Cluj-Napoca', 'Cluj', '400000', '0722123456', 'maria.popescu@example.com', 'Maria Popescu');

-- Insert order items
INSERT INTO comenzi_produse (id_comanda, id_produs, denumire_produs, pret_unitar, cantitate, subtotal) VALUES
(1, 1, 'Dulceață de Căpșuni de Argeș', 18.99, 2, 37.98),
(1, 3, 'Brânză de Burduf', 32.00, 1, 32.00),
(1, 5, 'Miere de Salcâm', 28.50, 2, 57.00),
(1, 9, 'Gem de Caise Banat', 21.50, 1, 21.50),
(2, 2, 'Zacuscă de Buzău', 15.50, 3, 46.50),
(2, 7, 'Telemea de Ibănești', 19.50, 2, 39.00),
(3, 4, 'Țuică de Prune Hunedoara', 45.00, 2, 90.00),
(3, 8, 'Pălincă de Pere Maramureș', 55.00, 1, 55.00),
(3, 10, 'Slănină Afumată Oltenia', 35.00, 1, 35.00),
(3, 6, 'Cârnați de Pleșcoi', 24.99, 1, 24.99);

-- Insert loyalty points
INSERT INTO puncte_fidelitate (id_utilizator, puncte_totale, puncte_folosite) VALUES
(2, 150, 50),
(3, 75, 0);

-- Insert loyalty points transactions
INSERT INTO tranzactii_puncte (id_utilizator, id_comanda, puncte, descriere) VALUES
(2, 1, 100, 'Puncte acumulate pentru comanda ORD-2024-001'),
(2, 3, 100, 'Puncte acumulate pentru comanda ORD-2024-003'),
(2, NULL, -50, 'Puncte folosite pentru reducere'),
(3, 2, 75, 'Puncte acumulate pentru comanda ORD-2024-002');

-- Insert site settings
INSERT INTO setari_site (cheie, valoare, descriere) VALUES
('site_name', 'Gusturi Românești', 'Numele site-ului'),
('site_description', 'Cea mai mare platformă online de produse tradiționale românești', 'Descrierea site-ului'),
('contact_email', 'contact@gusturi-romanesti.ro', 'Email-ul de contact'),
('contact_phone', '+40 721 234 567', 'Telefonul de contact'),
('contact_address', 'Strada Gusturilor Nr. 25, Sector 1, București 010101', 'Adresa fizică'),
('free_shipping_threshold', '150', 'Pragul pentru transport gratuit (RON)'),
('standard_shipping_cost', '15', 'Costul standard de transport (RON)'),
('loyalty_points_rate', '10', 'Rata de acumulare puncte fidelitate (1 punct la X RON)'),
('loyalty_points_value', '0.05', 'Valoarea unui punct de fidelitate (RON)');

-- Insert sample reviews
INSERT INTO recenzii (id_produs, id_utilizator, rating, titlu, comentariu, aprobat) VALUES
(1, 2, 5, 'Excelentă!', 'Cea mai bună dulceață de căpșuni pe care am gustat-o vreodată. Se simte gustul fructelor proaspete.', TRUE),
(3, 3, 4, 'Brânză foarte bună', 'Brânza are un gust autentic, exact ca cea pe care o făcea bunica. Recomand!', TRUE),
(5, 2, 5, 'Miere de calitate', 'Miere naturală, cu gust delicat și aromă specifică florilor de salcâm. Perfectă pentru ceai.', TRUE);

-- Insert sample logging entries
INSERT INTO jurnalizare (id_utilizator, actiune, detalii, ip) VALUES
(1, 'login', 'Autentificare reușită', '192.168.1.1'),
(1, 'product_add', 'Adăugare produs nou: Dulceață de Căpșuni de Argeș', '192.168.1.1'),
(2, 'login', 'Autentificare reușită', '192.168.1.2'),
(2, 'order_place', 'Plasare comandă: ORD-2024-001', '192.168.1.2'),
(3, 'login', 'Autentificare reușită', '192.168.1.3'),
(3, 'order_place', 'Plasare comandă: ORD-2024-002', '192.168.1.3');

-- Insert sample newsletter subscribers
INSERT INTO newsletter_abonati (email, nume, token_dezabonare) VALUES
('maria.popescu@example.com', 'Maria Popescu', MD5(CONCAT('maria.popescu@example.com', NOW()))),
('ion.ionescu@example.com', 'Ion Ionescu', MD5(CONCAT('ion.ionescu@example.com', NOW()))),
('ana.dumitrescu@example.com', 'Ana Dumitrescu', MD5(CONCAT('ana.dumitrescu@example.com', NOW())));

-- Insert sample contact form submissions
INSERT INTO contacte (nume, email, telefon, subiect, mesaj) VALUES
('Vasile Marin', 'vasile.marin@example.com', '0744123456', 'Întrebare despre produse', 'Aș dori să știu dacă produsele dvs. conțin conservanți.'),
('Elena Popa', 'elena.popa@example.com', '0755123456', 'Reclamație comandă', 'Am primit comanda incompletă. Lipsește un borcan de zacuscă.'),
('George Ionescu', 'george.ionescu@example.com', '0766123456', 'Colaborare', 'Sunt producător local de miere și aș dori să colaborăm.');

-- Create views for common queries

-- View: produse_active (active products with category and region)
CREATE VIEW produse_active AS
SELECT p.id, p.cod_produs, p.denumire, p.slug, p.descriere_scurta, p.pret, p.pret_redus, 
       p.greutate, p.stoc, p.imagine, p.recomandat, c.nume AS categorie, r.nume AS regiune
FROM produse p
JOIN categorii c ON p.id_categorie = c.id
JOIN regiuni r ON p.id_regiune = r.id
WHERE p.activ = TRUE AND c.activ = TRUE;

-- View: produse_cu_etichete (products with their tags)
CREATE VIEW produse_cu_etichete AS
SELECT p.id, p.denumire, p.slug, p.pret, c.nume AS categorie, r.nume AS regiune,
       GROUP_CONCAT(e.nume SEPARATOR ', ') AS etichete
FROM produse p
JOIN categorii c ON p.id_categorie = c.id
JOIN regiuni r ON p.id_regiune = r.id
LEFT JOIN produse_etichete pe ON p.id = pe.id_produs
LEFT JOIN etichete e ON pe.id_eticheta = e.id
WHERE p.activ = TRUE
GROUP BY p.id;

-- View: comenzi_recente (recent orders with customer info)
CREATE VIEW comenzi_recente AS
SELECT c.id, c.numar_comanda, c.status, c.total, c.data_plasare,
       CONCAT(u.prenume, ' ', u.nume) AS client, u.email, u.telefon
FROM comenzi c
JOIN utilizatori u ON c.id_utilizator = u.id
ORDER BY c.data_plasare DESC;

-- View: stoc_produse (product stock status)
CREATE VIEW stoc_produse AS
SELECT p.id, p.cod_produs, p.denumire, p.stoc,
       CASE 
           WHEN p.stoc = 0 THEN 'Stoc epuizat'
           WHEN p.stoc < 5 THEN 'Stoc limitat'
           ELSE 'În stoc'
       END AS status_stoc
FROM produse p
WHERE p.activ = TRUE
ORDER BY p.stoc ASC;

-- Create stored procedures

-- Procedure: adauga_in_cos (add to cart)
DELIMITER //
CREATE PROCEDURE adauga_in_cos(
    IN p_id_utilizator INT,
    IN p_id_produs INT,
    IN p_cantitate INT
)
BEGIN
    DECLARE v_stoc INT;
    DECLARE v_exista INT;
    
    -- Check if product exists and is in stock
    SELECT stoc INTO v_stoc FROM produse WHERE id = p_id_produs AND activ = TRUE;
    
    IF v_stoc IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Produsul nu există sau nu este activ';
    ELSEIF v_stoc < p_cantitate THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stoc insuficient';
    ELSE
        -- Check if product already in cart
        SELECT COUNT(*) INTO v_exista FROM cos_cumparaturi 
        WHERE id_utilizator = p_id_utilizator AND id_produs = p_id_produs;
        
        IF v_exista > 0 THEN
            -- Update quantity
            UPDATE cos_cumparaturi 
            SET cantitate = cantitate + p_cantitate 
            WHERE id_utilizator = p_id_utilizator AND id_produs = p_id_produs;
        ELSE
            -- Insert new cart item
            INSERT INTO cos_cumparaturi (id_utilizator, id_produs, cantitate)
            VALUES (p_id_utilizator, p_id_produs, p_cantitate);
        END IF;
    END IF;
END //
DELIMITER ;

-- Procedure: plaseaza_comanda (place order)
DELIMITER //
CREATE PROCEDURE plaseaza_comanda(
    IN p_id_utilizator INT,
    IN p_metoda_plata VARCHAR(20),
    IN p_id_voucher INT,
    IN p_adresa_livrare TEXT,
    IN p_oras_livrare VARCHAR(50),
    IN p_judet_livrare VARCHAR(50),
    IN p_cod_postal_livrare VARCHAR(10),
    IN p_telefon_livrare VARCHAR(15),
    IN p_email_livrare VARCHAR(100),
    IN p_nume_livrare VARCHAR(100),
    IN p_observatii TEXT,
    OUT p_numar_comanda VARCHAR(20),
    OUT p_id_comanda INT
)
BEGIN
    DECLARE v_subtotal DECIMAL(10, 2) DEFAULT 0;
    DECLARE v_cost_transport DECIMAL(10, 2) DEFAULT 15.00; -- Default shipping cost
    DECLARE v_valoare_reducere DECIMAL(10, 2) DEFAULT 0;
    DECLARE v_total DECIMAL(10, 2);
    DECLARE v_free_shipping_threshold DECIMAL(10, 2);
    DECLARE v_voucher_tip VARCHAR(10);
    DECLARE v_voucher_valoare DECIMAL(10, 2);
    DECLARE v_voucher_min_comanda DECIMAL(10, 2);
    DECLARE v_voucher_valid BOOLEAN DEFAULT FALSE;
    DECLARE v_year VARCHAR(4);
    DECLARE v_count INT;
    
    -- Start transaction
    START TRANSACTION;
    
    -- Get free shipping threshold from settings
    SELECT CAST(valoare AS DECIMAL(10,2)) INTO v_free_shipping_threshold 
    FROM setari_site WHERE cheie = 'free_shipping_threshold';
    
    -- Calculate subtotal from cart
    SELECT SUM(c.cantitate * p.pret) INTO v_subtotal
    FROM cos_cumparaturi c
    JOIN produse p ON c.id_produs = p.id
    WHERE c.id_utilizator = p_id_utilizator;
    
    -- Check if cart is empty
    IF v_subtotal IS NULL OR v_subtotal = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Coșul de cumpărături este gol';
        ROLLBACK;
    END IF;
    
    -- Apply free shipping if subtotal exceeds threshold
    IF v_subtotal >= v_free_shipping_threshold THEN
        SET v_cost_transport = 0;
    END IF;
    
    -- Apply voucher if provided
    IF p_id_voucher IS NOT NULL THEN
        SELECT tip, valoare, valoare_minima_comanda INTO v_voucher_tip, v_voucher_valoare, v_voucher_min_comanda
        FROM vouchere
        WHERE id = p_id_voucher AND activ = TRUE
        AND CURDATE() BETWEEN data_inceput AND data_expirare
        AND (utilizari_maxime IS NULL OR utilizari_curente < utilizari_maxime);
        
        IF v_voucher_tip IS NOT NULL AND v_subtotal >= v_voucher_min_comanda THEN
            SET v_voucher_valid = TRUE;
            
            IF v_voucher_tip = 'Procent' THEN
                SET v_valoare_reducere = v_subtotal * (v_voucher_valoare / 100);
            ELSE -- 'Valoare'
                SET v_valoare_reducere = v_voucher_valoare;
            END IF;
            
            -- Update voucher usage count
            UPDATE vouchere SET utilizari_curente = utilizari_curente + 1 WHERE id = p_id_voucher;
        ELSE
            SET p_id_voucher = NULL; -- Voucher not valid
        END IF;
    END IF;
    
    -- Calculate total
    SET v_total = v_subtotal - v_valoare_reducere + v_cost_transport;
    
    -- Generate order number (format: ORD-YYYY-XXXX)
    SET v_year = YEAR(CURDATE());
    SELECT COUNT(*) + 1 INTO v_count FROM comenzi WHERE YEAR(data_plasare) = v_year;
    SET p_numar_comanda = CONCAT('ORD-', v_year, '-', LPAD(v_count, 4, '0'));
    
    -- Insert order
    INSERT INTO comenzi (
        numar_comanda, id_utilizator, status, metoda_plata, 
        subtotal, cost_transport, id_voucher, valoare_reducere, total,
        adresa_livrare, oras_livrare, judet_livrare, cod_postal_livrare, 
        telefon_livrare, email_livrare, nume_livrare, observatii
    ) VALUES (
        p_numar_comanda, p_id_utilizator, 'Plasată', p_metoda_plata,
        v_subtotal, v_cost_transport, p_id_voucher, v_valoare_reducere, v_total,
        p_adresa_livrare, p_oras_livrare, p_judet_livrare, p_cod_postal_livrare,
        p_telefon_livrare, p_email_livrare, p_nume_livrare, p_observatii
    );
    
    -- Get the new order ID
    SET p_id_comanda = LAST_INSERT_ID();
    
    -- Insert order items from cart
    INSERT INTO comenzi_produse (id_comanda, id_produs, denumire_produs, pret_unitar, cantitate, subtotal)
    SELECT p_id_comanda, p.id, p.denumire, p.pret, c.cantitate, (p.pret * c.cantitate)
    FROM cos_cumparaturi c
    JOIN produse p ON c.id_produs = p.id
    WHERE c.id_utilizator = p_id_utilizator;
    
    -- Update product stock
    UPDATE produse p
    JOIN cos_cumparaturi c ON p.id = c.id_produs
    SET p.stoc = p.stoc - c.cantitate
    WHERE c.id_utilizator = p_id_utilizator;
    
    -- Add loyalty points (1 point per 10 RON spent)
    INSERT INTO tranzactii_puncte (id_utilizator, id_comanda, puncte, descriere)
    VALUES (p_id_utilizator, p_id_comanda, FLOOR(v_subtotal / 10), CONCAT('Puncte acumulate pentru comanda ', p_numar_comanda));
    
    -- Update loyalty points total
    UPDATE puncte_fidelitate
    SET puncte_totale = puncte_totale + FLOOR(v_subtotal / 10)
    WHERE id_utilizator = p_id_utilizator;
    
    -- If no loyalty record exists, create one
    IF ROW_COUNT() = 0 THEN
        INSERT INTO puncte_fidelitate (id_utilizator, puncte_totale)
        VALUES (p_id_utilizator, FLOOR(v_subtotal / 10));
    END IF;
    
    -- Clear the user's cart
    DELETE FROM cos_cumparaturi WHERE id_utilizator = p_id_utilizator;
    
    -- Log the order placement
    INSERT INTO jurnalizare (id_utilizator, actiune, detalii)
    VALUES (p_id_utilizator, 'order_place', CONCAT('Plasare comandă: ', p_numar_comanda));
    
    -- Commit transaction
    COMMIT;
END //
DELIMITER ;

-- Procedure: actualizeaza_status_comanda (update order status)
DELIMITER //
CREATE PROCEDURE actualizeaza_status_comanda(
    IN p_id_comanda INT,
    IN p_status VARCHAR(20),
    IN p_id_admin INT
)
BEGIN
    DECLARE v_numar_comanda VARCHAR(20);
    DECLARE v_status_vechi VARCHAR(20);
    
    -- Get current order info
    SELECT numar_comanda, status INTO v_numar_comanda, v_status_vechi
    FROM comenzi WHERE id = p_id_comanda;
    
    -- Update order status
    UPDATE comenzi SET status = p_status WHERE id = p_id_comanda;
    
    -- Update timestamp based on status
    CASE p_status
        WHEN 'Confirmată' THEN
            UPDATE comenzi SET data_confirmare = CURRENT_TIMESTAMP WHERE id = p_id_comanda;
        WHEN 'Expediată' THEN
            UPDATE comenzi SET data_expediere = CURRENT_TIMESTAMP WHERE id = p_id_comanda;
        WHEN 'Livrată' THEN
            UPDATE comenzi SET data_livrare = CURRENT_TIMESTAMP WHERE id = p_id_comanda;
        ELSE
            -- No timestamp update for other statuses
    END CASE;
    
    -- Log the status change
    INSERT INTO jurnalizare (id_utilizator, actiune, detalii)
    VALUES (p_id_admin, 'order_status_update', 
            CONCAT('Actualizare status comandă ', v_numar_comanda, ': ', v_status_vechi, ' -> ', p_status));
END //
DELIMITER ;

-- Create triggers

-- Trigger: before_product_price_update (log price changes)
DELIMITER //
CREATE TRIGGER before_product_price_update
BEFORE UPDATE ON produse
FOR EACH ROW
BEGIN
    IF OLD.pret != NEW.pret THEN
        INSERT INTO istoric_preturi (id_produs, pret_vechi, pret_nou, id_utilizator)
        VALUES (NEW.id, OLD.pret, NEW.pret, @current_user_id);
    END IF;
END //
DELIMITER ;

-- Trigger: after_order_status_update (send notification - placeholder)
DELIMITER //
CREATE TRIGGER after_order_status_update
AFTER UPDATE ON comenzi
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        -- In a real application, this would send notifications
        -- For this example, we'll just log it
        INSERT INTO jurnalizare (actiune, detalii)
        VALUES ('order_notification', CONCAT('Status comandă ', NEW.numar_comanda, ' actualizat la: ', NEW.status));
    END IF;
END //
DELIMITER ;

-- Create indexes for performance

-- Index for product searches
CREATE FULLTEXT INDEX idx_produse_search ON produse(denumire, descriere, descriere_scurta);

-- Index for order filtering
CREATE INDEX idx_comenzi_status ON comenzi(status);
CREATE INDEX idx_comenzi_data ON comenzi(data_plasare);

-- Index for user searches
CREATE INDEX idx_utilizatori_nume ON utilizatori(nume, prenume);

-- Index for product filtering
CREATE INDEX idx_produse_categorie ON produse(id_categorie);
CREATE INDEX idx_produse_regiune ON produse(id_regiune);
CREATE INDEX idx_produse_pret ON produse(pret);

-- =============================
-- ANALYTICAL SAMPLE QUERIES
-- =============================

-- Sample SQL Queries for Gusturi Românești E-commerce Platform
-- These queries demonstrate common operations for the database

-- 1. Get all active products with their category, region and tags
SELECT p.id, p.denumire, p.pret, p.greutate, p.stoc, 
       c.nume AS categorie, r.nume AS regiune,
       GROUP_CONCAT(e.nume SEPARATOR ', ') AS etichete
FROM produse p
JOIN categorii c ON p.id_categorie = c.id
JOIN regiuni r ON p.id_regiune = r.id
LEFT JOIN produse_etichete pe ON p.id = pe.id_produs
LEFT JOIN etichete e ON pe.id_eticheta = e.id
WHERE p.activ = TRUE
GROUP BY p.id
ORDER BY p.denumire;

-- 2. Get products by category with filtering
SELECT p.id, p.denumire, p.pret, p.greutate, p.stoc, p.imagine
FROM produse p
JOIN categorii c ON p.id_categorie = c.id
WHERE c.slug = 'dulceturi' -- Category slug
  AND p.activ = TRUE
  AND p.stoc > 0
  AND p.pret BETWEEN 10 AND 30 -- Price range
ORDER BY p.pret ASC;

-- 3. Get products by region
SELECT p.id, p.denumire, p.pret, p.greutate, p.stoc, p.imagine
FROM produse p
JOIN regiuni r ON p.id_regiune = r.id
WHERE r.nume = 'Transilvania'
  AND p.activ = TRUE
ORDER BY p.denumire;

-- 4. Get products with specific tags
SELECT p.id, p.denumire, p.pret, p.greutate, p.stoc, p.imagine
FROM produse p
JOIN produse_etichete pe ON p.id = pe.id_produs
JOIN etichete e ON pe.id_eticheta = e.id
WHERE e.nume IN ('produs-de-post', 'fara-aditivi')
  AND p.activ = TRUE
GROUP BY p.id
HAVING COUNT(DISTINCT e.nume) = 2 -- Products that have both tags
ORDER BY p.denumire;

-- 5. Search products by keyword
SELECT p.id, p.denumire, p.pret, p.descriere_scurta, p.imagine
FROM produse p
WHERE p.activ = TRUE
  AND (p.denumire LIKE '%miere%' OR p.descriere LIKE '%miere%' OR p.descriere_scurta LIKE '%miere%')
ORDER BY 
  CASE 
    WHEN p.denumire LIKE '%miere%' THEN 1
    WHEN p.descriere_scurta LIKE '%miere%' THEN 2
    ELSE 3
  END,
  p.denumire;

-- 6. Get user's cart with product details
SELECT c.id, p.denumire, p.pret, c.cantitate, (p.pret * c.cantitate) AS subtotal, p.imagine
FROM cos_cumparaturi c
JOIN produse p ON c.id_produs = p.id
WHERE c.id_utilizator = 2
ORDER BY c.data_adaugare DESC;

-- 7. Get user's order history
SELECT o.numar_comanda, o.data_plasare, o.status, o.total,
       COUNT(op.id) AS numar_produse
FROM comenzi o
JOIN comenzi_produse op ON o.id = op.id_comanda
WHERE o.id_utilizator = 2
GROUP BY o.id
ORDER BY o.data_plasare DESC;

-- 8. Get order details
SELECT o.numar_comanda, o.data_plasare, o.status, o.metoda_plata,
       o.subtotal, o.cost_transport, o.valoare_reducere, o.total,
       op.denumire_produs, op.pret_unitar, op.cantitate, op.subtotal AS subtotal_produs
FROM comenzi o
JOIN comenzi_produse op ON o.id = op.id_comanda
WHERE o.numar_comanda = 'ORD-2024-001'
ORDER BY op.denumire_produs;

-- 9. Get user's loyalty points
SELECT pf.puncte_totale, pf.puncte_folosite, (pf.puncte_totale - pf.puncte_folosite) AS puncte_disponibile,
       (pf.puncte_totale - pf.puncte_folosite) * 0.05 AS valoare_lei -- Assuming 1 point = 0.05 RON
FROM puncte_fidelitate pf
WHERE pf.id_utilizator = 2;

-- 10. Get loyalty points transactions
SELECT tp.puncte, tp.descriere, tp.data_tranzactie,
       CASE WHEN tp.puncte > 0 THEN 'Acumulate' ELSE 'Folosite' END AS tip
FROM tranzactii_puncte tp
WHERE tp.id_utilizator = 2
ORDER BY tp.data_tranzactie DESC;

-- 11. Get valid vouchers for a user
SELECT v.cod, v.tip, v.valoare, 
       CASE 
           WHEN v.tip = 'Procent' THEN CONCAT(v.valoare, '%')
           ELSE CONCAT(v.valoare, ' RON')
       END AS reducere,
       v.data_expirare, v.valoare_minima_comanda
FROM vouchere v
WHERE v.activ = TRUE
  AND CURDATE() BETWEEN v.data_inceput AND v.data_expirare
  AND (v.utilizari_maxime IS NULL OR v.utilizari_curente < v.utilizari_maxime)
ORDER BY v.data_expirare;

-- 12. Get product reviews
SELECT r.rating, r.titlu, r.comentariu, r.data_adaugare,
       CONCAT(u.prenume, ' ', LEFT(u.nume, 1), '.') AS autor
FROM recenzii r
JOIN utilizatori u ON r.id_utilizator = u.id
WHERE r.id_produs = 1
  AND r.aprobat = TRUE
ORDER BY r.data_adaugare DESC;

-- 13. Get product stock status
SELECT p.denumire, p.stoc,
       CASE 
           WHEN p.stoc = 0 THEN 'Stoc epuizat'
           WHEN p.stoc < 5 THEN 'Stoc limitat'
           ELSE 'În stoc'
       END AS status_stoc
FROM produse p
WHERE p.activ = TRUE
ORDER BY p.stoc ASC;

-- 14. Get sales by category
SELECT c.nume AS categorie, COUNT(op.id) AS numar_produse_vandute,
       SUM(op.subtotal) AS valoare_vanzari
FROM comenzi_produse op
JOIN comenzi o ON op.id_comanda = o.id
JOIN produse p ON op.id_produs = p.id
JOIN categorii c ON p.id_categorie = c.id
WHERE o.status NOT IN ('Anulată')
  AND o.data_plasare BETWEEN '2024-01-01' AND '2024-12-31'
GROUP BY c.id
ORDER BY valoare_vanzari DESC;

-- 15. Get sales by region
SELECT r.nume AS regiune, COUNT(op.id) AS numar_produse_vandute,
       SUM(op.subtotal) AS valoare_vanzari
FROM comenzi_produse op
JOIN comenzi o ON op.id_comanda = o.id
JOIN produse p ON op.id_produs = p.id
JOIN regiuni r ON p.id_regiune = r.id
WHERE o.status NOT IN ('Anulată')
  AND o.data_plasare BETWEEN '2024-01-01' AND '2024-12-31'
GROUP BY r.id
ORDER BY valoare_vanzari DESC;

-- 16. Get top selling products
SELECT p.denumire, COUNT(op.id) AS numar_vanzari,
       SUM(op.cantitate) AS cantitate_vanduta,
       SUM(op.subtotal) AS valoare_vanzari
FROM comenzi_produse op
JOIN comenzi o ON op.id_comanda = o.id
JOIN produse p ON op.id_produs = p.id
WHERE o.status NOT IN ('Anulată')
  AND o.data_plasare BETWEEN '2024-01-01' AND '2024-12-31'
GROUP BY p.id
ORDER BY cantitate_vanduta DESC
LIMIT 10;

-- 17. Get monthly sales report
SELECT 
    YEAR(o.data_plasare) AS an,
    MONTH(o.data_plasare) AS luna,
    COUNT(DISTINCT o.id) AS numar_comenzi,
    SUM(o.subtotal) AS vanzari_brute,
    SUM(o.valoare_reducere) AS reduceri,
    SUM(o.cost_transport) AS transport,
    SUM(o.total) AS vanzari_nete
FROM comenzi o
WHERE o.status NOT IN ('Anulată')
GROUP BY YEAR(o.data_plasare), MONTH(o.data_plasare)
ORDER BY an DESC, luna DESC;

-- 18. Get user activity log
SELECT j.actiune, j.detalii, j.ip, j.data_actiune
FROM jurnalizare j
WHERE j.id_utilizator = 2
ORDER BY j.data_actiune DESC
LIMIT 20;

-- 19. Get products that need restocking
SELECT p.cod_produs, p.denumire, p.stoc, c.nume AS categorie
FROM produse p
JOIN categorii c ON p.id_categorie = c.id
WHERE p.activ = TRUE AND p.stoc < 5
ORDER BY p.stoc ASC;

-- 20. Get product price history
SELECT ip.pret_vechi, ip.pret_nou, 
       CONCAT(ROUND(((ip.pret_nou - ip.pret_vechi) / ip.pret_vechi) * 100, 2), '%') AS modificare_procent,
       ip.data_modificare,
       CONCAT(u.prenume, ' ', u.nume) AS modificat_de
FROM istoric_preturi ip
LEFT JOIN utilizatori u ON ip.id_utilizator = u.id
WHERE ip.id_produs = 1
ORDER BY ip.data_modificare DESC;