# -*- coding: utf-8 -*-
import sys
if sys.getdefaultencoding() != 'utf-8':
    reload(sys)
    sys.setdefaultencoding('utf-8')
fout = open('processed.xml','w')
with open('categories.xml') as fin:
	for line in fin:
	#	print line
		fout.write(line.replace(u"\xa0", u" "))
fout.close()
