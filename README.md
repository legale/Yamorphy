# Yamorphy v0.0.4
PHP Morphological analyzer based on the OpenCorpora russian dictionary. This software provided under the MIT license.


## REQUIREMENTS
- PHP interpreter
- PHP cli
- PHP xml module 
- PHP bz2 module
- PHP mysqli module
- Mysql server
- Wget

To install these packages run `apt install wget php7.2 php7.2-cli php7.2-xml php7.2-bz2 php7.2-mysqli mysql-server`

## INSTALLATION
Clone repo:
`git clone https://github.com/legale/Yamorphy`

### Setup your config.php
`cd ./Yamorphy && cp config.php.sample config.php`
Edit config.php to setup database parameters

### Download dictionary archive
`wget http://opencorpora.org/files/export/dict/dict.opcorpora.xml.bz2 -P ./xml/`

### Convert xml dictionary into set of serialized arrays files
`php xml_parse.php`

### Create and fill database
`php fill_db.php`

Done!

## HOWTO USE

### search all word forms
run:
`php find.php find слово`
to find word 'слово'

### search defined word form
run:
`php find.php find слово '28|34'`
to find word 'слово' singular and nominative case form

run: 
`php find.php find слово '29|34'`
to find word 'слово' plural and nominative case form

