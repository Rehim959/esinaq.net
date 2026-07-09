SET NAMES utf8mb4;

-- Nümunə suallar: 5-ci sinif, Azərbaycan sektoru, Riyaziyyat
INSERT INTO questions (subject_id, grade, sector, question_text, option_a, option_b, option_c, option_d, option_e, correct_option, difficulty)
SELECT s.id, 5, 'az', '25 + 17 nəticəsi neçədir?', '40', '42', '41', '43', '39', 'B', 'easy'
FROM subjects s WHERE s.code = 'riyaziyyat';

INSERT INTO questions (subject_id, grade, sector, question_text, option_a, option_b, option_c, option_d, option_e, correct_option, difficulty)
SELECT s.id, 5, 'az', '8 × 7 = ?', '54', '56', '63', '48', '58', 'B', 'easy'
FROM subjects s WHERE s.code = 'riyaziyyat';

INSERT INTO questions (subject_id, grade, sector, question_text, option_a, option_b, option_c, option_d, option_e, correct_option, difficulty)
SELECT s.id, 5, 'az', '100-dən 37 çıxanda nə qalır?', '73', '63', '67', '62', '72', 'B', 'medium'
FROM subjects s WHERE s.code = 'riyaziyyat';

INSERT INTO questions (subject_id, grade, sector, question_text, option_a, option_b, option_c, option_d, option_e, correct_option, difficulty)
SELECT s.id, 5, 'az', 'Bir saatda neçə dəqiqə var?', '30', '60', '100', '24', '90', 'B', 'easy'
FROM subjects s WHERE s.code = 'riyaziyyat';

INSERT INTO questions (subject_id, grade, sector, question_text, option_a, option_b, option_c, option_d, option_e, correct_option, difficulty)
SELECT s.id, 5, 'az', '3/4 + 1/4 = ?', '1', '1/2', '2/4', '3/8', '4/8', 'A', 'medium'
FROM subjects s WHERE s.code = 'riyaziyyat';

INSERT INTO questions (subject_id, grade, sector, question_text, option_a, option_b, option_c, option_d, option_e, correct_option, difficulty)
SELECT s.id, 5, 'az', 'Kvadratın 4 tərəfi bərabərdir. Bu doğru mudur?', 'Bəli', 'Xeyr', 'Bəzən', 'Yalnız düzbucaqlıda', 'Bilinmir', 'A', 'easy'
FROM subjects s WHERE s.code = 'riyaziyyat';

INSERT INTO questions (subject_id, grade, sector, question_text, option_a, option_b, option_c, option_d, option_e, correct_option, difficulty)
SELECT s.id, 5, 'az', '15-in 20%-i neçədir?', '2', '3', '4', '5', '6', 'B', 'medium'
FROM subjects s WHERE s.code = 'riyaziyyat';

INSERT INTO questions (subject_id, grade, sector, question_text, option_a, option_b, option_c, option_d, option_e, correct_option, difficulty)
SELECT s.id, 5, 'az', 'Ən böyük tək ədəd hansıdır?', '8', '9', '10', '7', '6', 'B', 'easy'
FROM subjects s WHERE s.code = 'riyaziyyat';

INSERT INTO questions (subject_id, grade, sector, question_text, option_a, option_b, option_c, option_d, option_e, correct_option, difficulty)
SELECT s.id, 5, 'az', 'Bir düzbucaqlının sahəsi 24 sm², eni 4 sm-dirsə, uzunluğu?', '5', '6', '8', '10', '12', 'B', 'hard'
FROM subjects s WHERE s.code = 'riyaziyyat';

INSERT INTO questions (subject_id, grade, sector, question_text, option_a, option_b, option_c, option_d, option_e, correct_option, difficulty)
SELECT s.id, 5, 'az', '0.5 + 0.25 = ?', '0.7', '0.75', '0.8', '1', '0.55', 'B', 'medium'
FROM subjects s WHERE s.code = 'riyaziyyat';

-- Məntiq nümunələri
INSERT INTO questions (subject_id, grade, sector, question_text, option_a, option_b, option_c, option_d, option_e, correct_option, difficulty)
SELECT s.id, 5, 'az', 'Hansı sıra məntiqlidir: 2, 4, 8, 16, ?', '18', '24', '32', '20', '30', 'C', 'medium'
FROM subjects s WHERE s.code = 'mentiq';

INSERT INTO questions (subject_id, grade, sector, question_text, option_a, option_b, option_c, option_d, option_e, correct_option, difficulty)
SELECT s.id, 5, 'az', 'Alma meyvədir. Armud da meyvədir. Bəs kök?', 'Meyvə', 'Tərəvəz', 'Taxıl', 'Ədviyyat', 'İçki', 'B', 'easy'
FROM subjects s WHERE s.code = 'mentiq';

INSERT INTO questions (subject_id, grade, sector, question_text, option_a, option_b, option_c, option_d, option_e, correct_option, difficulty)
SELECT s.id, 5, 'az', 'Əgər bütün A-lar B-dirsə və C bir A-dırsa, C nədir?', 'B', 'A deyil', 'Bilinmir', 'C', 'Heç biri', 'A', 'hard'
FROM subjects s WHERE s.code = 'mentiq';

INSERT INTO questions (subject_id, grade, sector, question_text, option_a, option_b, option_c, option_d, option_e, correct_option, difficulty)
SELECT s.id, 5, 'az', 'Saat 3-də əqrəb harada durur?', '12-də', '3-də', '6-da', '9-da', '1-də', 'B', 'easy'
FROM subjects s WHERE s.code = 'mentiq';

INSERT INTO questions (subject_id, grade, sector, question_text, option_a, option_b, option_c, option_d, option_e, correct_option, difficulty)
SELECT s.id, 5, 'az', 'Hansı söz digərlərindən fərqlidir?', 'Qırmızı', 'Mavi', 'Yaşıl', 'Masa', 'Sarı', 'D', 'easy'
FROM subjects s WHERE s.code = 'mentiq';
