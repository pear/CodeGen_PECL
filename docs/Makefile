all: manual.html manual.pdf

manual.html: manual.xml
	docbook2html -u manual.xml

manual.pdf: manual.xml
	docbook2pdf manual.xml

