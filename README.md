# wp-cli-auto-updater
Provides an Auto Update feature similar to Brew Auto updater
### Installation
```
wp package install git@github.com:timnashcouk/wp-cli-auto-updater.git
```
### Usage
Will recheck once every 12 hours before running command
Does not check when running `wp cli` commands
Can skip checks by passing the `--disable-autoupdate` during a command
Or skipped entirely by adding `disable-autoupdate` within a config for a specific command  for example:
```
core config:
   disable-autoupdate: true
```
Within wp-cli.yml
