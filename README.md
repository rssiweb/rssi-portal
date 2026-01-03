1 To Open Terminal Win + R => type cmd 2 Go to website folder, type following in terminal - E: - cd RSSIWebsite 3 See the changes - git status 4 Stage the changes (if modified the existing file) - git stage -u 5 Stage the changes (if adding new files) - git add <path_to_file> 6 Commit the changes - git commit -m "your message" 7 Push the changes to remote - git push origin -u master

One Time setup respository on Github

create a new repository and copy <remote_URL_from_github> (https://github.com/sahasomnath/RSSIWebsite.git)
open the terminal in website folder(step 1 and step 2)
git init
git add . (to add all files in current directory)
git remote add origin <remote_URL_from_github>
git config --global user.email "you@example.com"
git config --global user.name "Somnath"
Step 1 git pull

Step 2 git rm -rf Folder1

Step 3 git rm -rf Folder1

Step 4 git add .

Step 5 git commit -m “Folder1 deleted”

Step 6 git push

E:\RSSIWebsite>git fetch --all Fetching origin

E:\RSSIWebsite>git reset --hard origin/rssiweb HEAD is now at 7ef1a78 lazy loading

git fetch origin d75e8d81318243376cf355c5ec4dfa84f3b git checkout FETCH_HEAD git checkout -b "rssiweb" git push --all git push --set-upstream origin rssiweb

SELECT table_name FROM information_schema.tables WHERE table_schema='public' AND table_type='BASE TABLE'

***** TO CHECK OFFLINE
docker-compose up --build -d
docker-compose down
docker-compose ps
docker-compose restart
docker system prune
git stash

git fetch origin
git reset --hard origin/master
git reset --hard HEAD~3
http://web.local/generate-hash-for/2311 -> D:\services\.env
http://rssi.in/generate-hash-for/2311 -> digital ocean

SELECT column_name, ordinal_position
FROM information_schema.columns
WHERE table_name = 'rssimyaccount_members'
ORDER BY ordinal_position;


git pull --rebase origin master

ALTER TABLE fees
ADD COLUMN id SERIAL PRIMARY KEY;

ALTER TABLE fees ALTER COLUMN fees TYPE numeric USING (fees::numeric);

SELECT column_name FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'rssimyprofile_student';

INSERT INTO [Table to copy To]
SELECT [Columns to Copy]
FROM [Table to copy From]


CREATE TABLE test_form (LIKE certificate INCLUDING ALL);

UPDATE leavedb_leavedb
SET doc = REPLACE(doc, 'https://drive.google.com/open?id=', 'https://drive.google.com/file/d/')
WHERE doc is not null;

UPDATE leavedb_leavedb
SET doc = CONCAT(doc,'/view')
WHERE doc is not null;

///PDE generate
npm install puppeteer
cd /d/RSSILogin/app/html/rssi-member
node generatePDF.js

<!-- to update vendor file -->
export COMPOSER_PROCESS_TIMEOUT=600
composer install --no-dev --prefer-dist (ensures only required files are downloaded)

<!-- to add any new API key, correct way to use -->
For API add - for local use add it in .env file under services and update docker-compose.override and docker-compose file as well. - this is for local use. call boostrap file and call the key in the php file.

For production - update publish.yml file as well under service and update in git under code>settings > Security> Secrets and variables and update key there.

cd D:\RSSILogin\app - our composer is here.
