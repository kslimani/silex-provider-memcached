usage:
	@printf "Usage: make {test|cc|cs|clean}\n\n"
	@printf "   test    Run PHP test suites\n"
	@printf "   cc      Generate PHP code coverage report\n"
	@printf "   cs      Run PHP Coding Standards Fixer\n"
	@printf "   clean   Delete code coverage report\n\n"
clean:
	rm -rf coverage-html
test:
	vendor/bin/phpunit --stderr
cc:
	$(MAKE) clean
	vendor/bin/phpunit --stderr --coverage-html coverage-html
cs:
	vendor/bin/php-cs-fixer -vv fix src
	vendor/bin/php-cs-fixer -vv fix tests
