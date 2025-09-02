GitHubからこのプロジェクトのリポジトリをクローンします。

クローンしたディレクトリ内で、Docker Composeを実行します。

コンテナが起動したら、掲示板のテーブルを作成します。
docker-compose exec db mysql -u root -pexample_password example_db -e "CREATE TABLE IF NOT EXISTS bbs_entries (id INT AUTO_INCREMENT PRIMARY KEY, body TEXT NOT NULL, image_filename VARCHAR(255), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);"

ウェブブラウザに掲示板の画面が表示されることを確認します。
