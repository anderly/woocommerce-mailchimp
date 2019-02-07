#!/usr/bin/env bash
# usage: travis.sh before|after

set -e

INITIAL_DIR=$PWD

say() {
  echo -e "$1"
}

install_wc() {

  cd $INITIAL_DIR

	if [ ! -d ../woocommerce ]; then
		git clone https://github.com/woocommerce/woocommerce ../woocommerce
		cd ../woocommerce
		git checkout $WC_BRANCH
	fi

	say "WooCommerce Installed"

}

if [ $1 == 'before' ]; then

	install_wc

fi
