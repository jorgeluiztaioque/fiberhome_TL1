#!/usr/bin/python
# Jorge Luiz Taioque
# jorgeluiztaioque at gmail dot com 
#
# Usege fhtli1_get_rxpower.py  [ip_tl1_server] [ip_olt] [pon_slot-pon_port]
# use last two numbers of pon port like 1-1
#
#

import time
import sys
from socket import *

#Dados to connect TL1
user='1'
pass='1'


tl1host = sys.argv[1]
tl1port = 3337
oltip = sys.argv[2]
oltport = 'NA-NA-'+sys.argv[3]
name = []
onuid = []


#connecting socket on TL1 service
s = socket(AF_INET, SOCK_STREAM)    
s.connect((tl1host, tl1port))
s.send('LOGIN:::CTAG::UN='+user+',PWD='+pass+';')
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

def readrxpower ():
	name, onuid = getonuid()
	count = 0
	count2 = 0
	for onuid2 in onuid:
		rxpower = []
		s.send('LST-OMDDM::OLTID='+oltip+',PONID='+oltport+',ONUIDTYPE=MAC,ONUID='+onuid2+':CTAG::;')
		time.sleep(2)
		data = s.recv(8000)
		for line in data.splitlines():
			fields = line.split()
			if len(fields) >= 13:
				rxpower.append(fields[1])

		if count >=1:		
			print name[count2], onuid[count2], rxpower[1]
		count = 1
		count2 = count2 + 1
	logout()
	return name, onuid, rxpower

readrxpower()
