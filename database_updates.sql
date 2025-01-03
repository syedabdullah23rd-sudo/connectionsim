-- Experience Table
CREATE TABLE experience (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    company_name VARCHAR(255),
    position VARCHAR(255),
    location VARCHAR(255),
    start_date DATE,
    end_date DATE,
    currently_working BOOLEAN DEFAULT FALSE,
    description TEXT,
    company_logo VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Education Table
CREATE TABLE education (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    school_name VARCHAR(255),
    degree VARCHAR(255),
    field_of_study VARCHAR(255),
    start_date DATE,
    end_date DATE,
    grade VARCHAR(50),
    activities TEXT,
    school_logo VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Skills Table
CREATE TABLE skills (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    skill_name VARCHAR(255),
    endorsements INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Skill Endorsements Table
CREATE TABLE skill_endorsements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    skill_id INT,
    endorser_id INT,
    endorsed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (skill_id) REFERENCES skills(id),
    FOREIGN KEY (endorser_id) REFERENCES users(id)
);

-- Update users table
ALTER TABLE users 
ADD headline VARCHAR(255) AFTER full_name,
ADD location VARCHAR(255) AFTER headline,
ADD industry VARCHAR(255) AFTER location,
ADD current_position VARCHAR(255) AFTER industry,
ADD website VARCHAR(255) AFTER current_position,
ADD open_to_work BOOLEAN DEFAULT FALSE; 