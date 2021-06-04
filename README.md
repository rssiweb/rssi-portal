1 To Open Terminal
	Win + R => type cmd
2 Go to website folder, type following in terminal
	- E: 
	- cd RSSIWebsite
3 See the changes
	- git status
4 Stage the changes (if modified the existing file)
	- git stage -u
5 Stage the changes (if adding new files)
	- git add <path_to_file>
6 Commit the changes
	- git commit -m "your message"
7 Push the changes to remote
	- git push origin -u master

One Time setup respository on Github
 - create a new repository and copy <remote_URL_from_github> (https://github.com/sahasomnath/RSSIWebsite.git)
 - open the terminal in website folder(step 1 and step 2)
 - git init
 - git add . (to add all files in current directory)
 - git remote add origin <remote_URL_from_github>
 - git config --global user.email "you@example.com"
 - git config --global user.name "Somnath"



Step 1
git pull

Step 2
git rm -rf Folder1

Step 3
git rm -rf Folder1

Step 4
git add .

Step 5
git commit -m “Folder1 deleted”

Step 6
git push


E:\RSSIWebsite>git fetch --all
Fetching origin

E:\RSSIWebsite>git reset --hard origin/rssiweb
HEAD is now at 7ef1a78 lazy loading


git fetch origin d75e8d81318243376cf355c5ec4dfa84f3b
git checkout FETCH_HEAD
git checkout -b "rssiweb"
git push --all
git push --set-upstream origin rssiweb


pipenv run flask run


Password change
--------------------------
heroku Login - rssi.connect@gmail.com
More
Run console
flask generate-hash-for 2310
Run
Save the session and copy the encripted value and paste it to
Settings
Reveal config variable
paste the codes (SECURE_USER, INDIGO_CODE, SECURE1_USER, PASSWORD_CODE)
DO NOT REMOVE Salt

Change in .env file offline
---------------------------------
Run new terminal and type
pipenv run flask generate-hash-for 2310
copy the code and replace it.

select class from rssimyaccount_members where associatenumber='VLKO21034'

UPDATE rssimyaccount_members
SET class = 'LG4 Accountancy - Mon, Wed, Fri - 4 p.m. to 4:45 p.m.<br>' 
WHERE associatenumber='VLKO21034';

UPDATE rssimyprofile_student
SET photourl = 'https://res.cloudinary.com/hs4stt5kg/image/upload/v1622616675/students/Aditya1.jpg' 
WHERE student_id='ALKO21059';

ALTER TABLE rssimyaccount_members 
ALTER COLUMN effectivedate TYPE VARCHAR(512);

update employee 
set department = null, name = null, bloodgroup = null
where employeeid=2;

ALTER TABLE rssimyprofile_student
  ALTER selectdateofformsubmission DROP DEFAULT
 ,ALTER selectdateofformsubmission type timestamp USING selectdateofformsubmission::timestamp
 ,ALTER selectdateofformsubmission SET DEFAULT '1970-01-01 01:00:00'::timestamp;


 DELETE from `tablename` WHERE `id` IN (1, 5 , 7);

 DELETE from rssimyaccount_members WHERE __hevo_id IN (21);

 select lastupdatedon,__hevo_id,fullname from rssimyaccount_members where associatenumber='VBKP21033';


 and Col32 is null or Col32 > date '"&text(datevalue("2021/06/3"),"yyyy-mm-dd")&"'

 and Col33 is null or Col33 > date '"&text(datevalue("2021/06/1"),"yyyy-mm-dd")&"'