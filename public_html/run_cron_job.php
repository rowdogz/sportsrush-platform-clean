<?php
// Set the command to run the Python script with the date argument
$command = '/usr/bin/python3 /home3/editor/scripts/real-score-updater.py ' . date('Y-m-d', strtotime('+3 days'));

// Execute the command
$output = shell_exec($command);

// Display the output
echo "<pre>$output</pre>";
?>
