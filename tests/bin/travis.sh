#!/usr/bin/env bash
# usage: travis.sh before|after

set -e

INITIAL_DIR=$PWD

say() {
  echo -e "$1"
}

install_wc() {

  cd $INITIAL_DIR

	# if [ ! -d ../woocommerce ]; then
	# 	git clone https://github.com/woocommerce/woocommerce ../woocommerce
	# 	cd ../woocommerce
	# 	git checkout $WC_BRANCH
	# fi

	# say "WooCommerce Installed"

	# place a copy of woocommerce where the unit tests etc. expect it to be
	mkdir -p "../woocommerce"
	if [ $WC_VERSION == 'latest' ]; then
		# Get the latest WooCommerce release
		curl -L https://api.github.com/repos/woocommerce/woocommerce/releases/$WC_VERSION |
			grep '"tarball_url":' | #Get tarball url line
			sed -E 's/.*"([^"]+)".*/\1/' | #Get tarball url
			xargs -I {} curl -sL "{}" | #Capture the argument and use to download the tarball
			tar --strip-components=1 -zx -C "../woocommerce" #Extract
	else
		# Get the specified WooCommerce release
		curl -sL https://api.github.com/repos/woocommerce/woocommerce/tarball/$WC_VERSION | tar --strip-components=1 -zx -C "../woocommerce"
	fi

	say "WooCommerce Installed"

}

if [ $1 == 'before' ]; then

	install_wc

fi
