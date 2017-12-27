1. Place these in your /Library/LaunchDaemons folder.

2. Update the paths when adding these to your system
   Check the comments in the .plist files for details

	<key>WorkingDirectory</key>
	<key>ProgramArguments</key>
	<key>StandardOutPath</key>
	<key>StandardErrorPath</key>

3. Run these commands
	
	sudo launchctl load -w edu.si.libraries.macaw.CollectStatistics.plist
	sudo launchctl load -w edu.si.libraries.macaw.Export.plist
	sudo launchctl load -w edu.si.libraries.macaw.Import.plist
		
4. ....

5. Profit!