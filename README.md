Allows Moodle administrators to package and deploy sets of Moodle plugins, site settings, language packs... (see http://docs.moodle.org/en/Development:Moodle_flavours for more info)

# Description
A flavour is a packaged set of Moodle site settings, plugins and language packs. Moodle Administrators will be able to create a flavour from their installation, selecting which settings, plugins and language packs will be packaged into a compressed file. They can then, share the package with the Moodle community, store it as a backup or use it to replicate the flavour to other installations they manages. It could also be useful for administrators with little Moodle experienced, allowing them to explore the Moodle settings and setup recommended by others.

**Warning!** To export and import settings it is better to use [Admin presets block](https://moodle.org/plugins/block_admin_presets) as the process used here is not reliable. The longer explanation is that this is only using get\_config/set\_config functions, block\_admin\_presets is using Moodle's admin settings API.

## Install from a compressed .zip
* Extract the compressed file data
* Rename the main folder to flavours
* Copy it to local/ folder
* Click 'Notifications' link on the frontpage administration block

## Install using git
* Navigate to Moodle root folder
* **git clone git://github.com/dmonllao/moodle-local_flavours local/flavours**
* **cd local/flavours**
* **git checkout MOODLE_XY_STABLE**
* **php admin/cli/upgrade.php**

# Usage

Available under Administration block -> Site settings -> Server -> Package a flavour & Deploy a flavour

# License

[GPL-2.0](http://www.gnu.org/licenses/gpl-2.0.txt)

# More info
* [Source code repository](https://github.com/dmonllao/moodle-local_flavours)
* [moodle.org plugins page](https://moodle.org/plugins/local_flavours)
* [Documentation](http://docs.moodle.org/en/Development:Moodle_flavours)
* [Original Moodle tracker entry](http://tracker.moodle.org/browse/CONTRIB-2948)
