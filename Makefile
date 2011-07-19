CURDIR := $(shell pwd)

DSTDIR := "/usr/local/bin"

help:
	@echo "make targets:"
	@echo "    install    creates single-executable '$(DSTDIR)/asciidia'"
	
install:
	@php -dphar.readonly=0 $(CURDIR)/phar/install.php $(DSTDIR)
