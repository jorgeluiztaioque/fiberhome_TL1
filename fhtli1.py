#!/usr/bin/python
# Jorge Luiz Taioque
# jorgeluiztaioque at gmail dot com 
#
# Usege fhtl1.py [ip_tl1_server] [ip_olt] [pon_port]
# use last two numbers of pon port link 1-1
#
#


import time
import sys
from socket import *

tl1host = sys.argv[1]
tl1port = 3337
oltip = sys.argv[2]
oltport = 'NA-NA-'+sys.argv[3]
name = []
onuid = []


#connecting socket on TL1 service
s = socket(AF_INET, SOCK_STREAM)    
s.connect((tl1host, tl1port))
s.send('LOGIN:::CTAG::UN=1,PWD=1;')
time.sleep(2)

	
def logout ():
	s.send('LOGOUT:::CTAG::;')
	time.sleep(2)
	s.close()
	return

def getonus ():
	s.send('LST-ONU::OLTID='+oltip+',PONID='+oltport+':CTAG::;')
	time.sleep(2)
	data = s.recv(80000)
	return data

def getonuid ():
	data = getonus()
	for line in data.splitlines():
		fields = line.split()
		if len(fields) >= 11:
			name.append(fields[3:6])
			onuid.append(fields[10])
	return name, onuid

def showonus ():
	data = getonus()
	logout();
	print data
	return data

showonus()





