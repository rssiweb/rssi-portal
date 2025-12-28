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