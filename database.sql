CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(80) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('user','admin') NOT NULL DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS films (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(160) NOT NULL,
  genre VARCHAR(120) NOT NULL,
  year INT NOT NULL,
  duration INT NOT NULL,
  rating DECIMAL(3,1) NOT NULL,
  director VARCHAR(160) NOT NULL,
  country VARCHAR(120) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_film_title_year (title, year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS desired_films (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  film_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_film (user_id, film_id),
  CONSTRAINT fk_desired_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_desired_film FOREIGN KEY (film_id) REFERENCES films(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS photos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(160) NOT NULL,
  path VARCHAR(255) NOT NULL,
  source VARCHAR(40) NOT NULL DEFAULT 'local',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_photo_path (path)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS photo_ratings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  photo_id INT NOT NULL,
  rating TINYINT NOT NULL,
  comment VARCHAR(255) NULL,
  rated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_photo (user_id, photo_id),
  CONSTRAINT fk_rating_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_rating_photo FOREIGN KEY (photo_id) REFERENCES photos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO films (title, genre, year, duration, rating, director, country) VALUES
('The Shawshank Redemption','Drama',1994,142,9.3,'Frank Darabont','USA'),
('The Godfather','Crime, Drama',1972,175,9.2,'Francis Ford Coppola','USA'),
('The Dark Knight','Action, Crime',2008,152,9.0,'Christopher Nolan','UK/USA'),
('Pulp Fiction','Crime, Drama',1994,154,8.9,'Quentin Tarantino','USA'),
('Inception','Action, Adventure',2010,148,8.8,'Christopher Nolan','USA/UK'),
('The Matrix','Action, Sci-Fi',1999,136,8.7,'Lana Wachowski','USA'),
('Life Is Beautiful','Comedy, Drama',1997,116,8.6,'Roberto Benigni','Italy'),
('A Low Rated Test Film','Drama',2020,95,4.3,'Demo Director','Croatia')
ON DUPLICATE KEY UPDATE title = VALUES(title);

INSERT INTO photos (title, path, source) VALUES
('Dick Johnson Is Dead','public/images/photo1.jpg','local'),
('My Little Pony: A New Generation','public/images/photo2.jpg','local'),
('Sankofa','public/images/photo3.jpg','local'),
('The Starling','public/images/photo4.jpg','local')
ON DUPLICATE KEY UPDATE title = VALUES(title);
