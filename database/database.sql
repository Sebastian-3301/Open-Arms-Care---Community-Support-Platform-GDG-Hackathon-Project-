
CREATE TABLE IF NOT EXISTS individual_registrations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(400) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  city VARCHAR(400) NOT NULL,
  interest VARCHAR(400) NOT NULL,
  status ENUM('new','reviewed','archived') NOT NULL DEFAULT 'new',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_individual_status (status),
  INDEX idx_individual_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS organisation_registrations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  organisation_name VARCHAR(400) NOT NULL,
  registration_number VARCHAR(100) NULL,
  location VARCHAR(400) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  interest VARCHAR(400) NOT NULL,
  status ENUM('new','reviewed','archived') NOT NULL DEFAULT 'new',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_org_status (status),
  INDEX idx_org_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS orphanage_registrations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  orphanage_name VARCHAR(400) NOT NULL,
  administrator_name VARCHAR(400) NOT NULL,
  location VARCHAR(400) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  children_total INT UNSIGNED NOT NULL,
  children_age_0_5 INT UNSIGNED NOT NULL DEFAULT 0,
  children_age_6_12 INT UNSIGNED NOT NULL DEFAULT 0,
  children_age_13_18 INT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('new','reviewed','archived') NOT NULL DEFAULT 'new',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_orphanage_status (status),
  INDEX idx_orphanage_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS money_donation_intents (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  frequency ENUM('once','monthly') NOT NULL,
  amount INT UNSIGNED NOT NULL,
  donor_name VARCHAR(400) NOT NULL,
  donor_email VARCHAR(254) NOT NULL,
  preferred_orphanage VARCHAR(400) NULL,
  status ENUM('new','reviewed','archived') NOT NULL DEFAULT 'new',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_money_status (status),
  INDEX idx_money_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS goods_donation_intents (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  categories JSON NOT NULL,
  description VARCHAR(2000) NOT NULL,
  quantity VARCHAR(100) NULL,
  item_condition VARCHAR(40) NULL,
  fulfilment_mode VARCHAR(80) NULL,
  phone VARCHAR(30) NOT NULL,
  status ENUM('new','reviewed','archived') NOT NULL DEFAULT 'new',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_goods_status (status),
  INDEX idx_goods_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS contact_messages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sender_name VARCHAR(400) NOT NULL,
  sender_email VARCHAR(254) NOT NULL,
  subject VARCHAR(400) NULL,
  message VARCHAR(4000) NOT NULL,
  status ENUM('new','reviewed','archived') NOT NULL DEFAULT 'new',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_contact_status (status),
  INDEX idx_contact_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS document_uploads (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  orphanage_registration_id INT UNSIGNED NOT NULL,
  original_name VARCHAR(180) NOT NULL,
  storage_name VARCHAR(255) NOT NULL UNIQUE,
  media_type VARCHAR(80) NOT NULL,
  size_bytes INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_documents_orphanage (orphanage_registration_id),
  CONSTRAINT fk_documents_orphanage
    FOREIGN KEY (orphanage_registration_id)
    REFERENCES orphanage_registrations(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB;
