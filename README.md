# Custom mailing activity

This activity allows custom mailing creation with following parameters :

Send an email to each user enrolled in the course :

* at course enrol
* x days(s) after first launch of a scorm
* x days(s) after last launch of a scorm
  
This plugin can also generate and send a PDF certificate linked to the mailing when conditions are met.
This feature requires Mod customcert plugin https://moodle.org/plugins/mod_customcert

## Installation

There are two installation methods available. 

Follow one of these, then log into your Moodle site as an administrator and visit the notifications page to complete the install.

### Git

This requires Git being installed. If you do not have Git installed, please visit the [Git website](https://git-scm.com/downloads "Git website").

Once you have Git installed, simply visit your Moodle mod directory and clone the repository using the following command.

```
git clone https://github.com/cbluesprl/moodle-mod_custommailing.git custommailing
```

Or add it with submodule command if you use submodules.

```
git submodule add https://github.com/cbluesprl/moodle-mod_custommailing.git mod/custommailing
```

### Download the zip

Visit the [Moodle plugins website](https://moodle.org/plugins/mod_custommailing "Moodle plugins website") and download the zip corresponding to the version of Moodle you are using. Extract the zip and place the 'custommailing' folder in the mod folder in your Moodle directory.

## License

Licensed under the [GNU GPL License](http://www.gnu.org/copyleft/gpl.html).