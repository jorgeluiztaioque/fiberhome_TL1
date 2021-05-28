<?php
/*
COMMAND LIST DOCUMENTATION


# LOGIN
LOGIN:::CTAG::UN=login,PWD=senha;

# LOGOUT
LOGOUT:::CTAG::;


# LISTA ONUS
LST-ONU::OLTID=ip_olt:CTAG::;

# LIBERA ONU
ADD-ONU::OLTID=ip_olt,PONID=1-1-1-5:CTAG::ONUTYPE=AN5506-04-B2,NAME=BRUNO VIVIANI,ONUID=FHTT99999999; 

# DELETA ONU
DEL-ONU::OLTID=ip_olt,PONID=1-1-1-5:CTAG::ONUTYPE=AN5506-04-B2,ONUID=FHTT99999999; 

# CONFIGURA PPPOE (o comando deve ser dado porta por porta da onu, ou seja 4x, alterando o uport)
SET-WANSERVICE::OLTID=ip_olt,PONID=1-1-1-5,ONUIDTYPE=LOID,ONUID=FHTT99999999:CTAG::STATUS=1,MODE=2,CONNTYPE=2,VLAN=2000,COS=1,QOS=2,NAT=1,IPMODE=3,PPPOEPROXY=2,PPPOEUSER=usuario,PPPOEPASSWD=senha,PPPOENAME=,PPPOEMODE=1,UPORT=1;

# SETA PROFILE ONU BANDWIDTH
CFG-ONUBW::OLTID=ip_olt,PONID=1-1-1-5,ONUIDTYPE=LOID,ONUID=FHTT99999999:CTAG::UPBW=50M;

# ALTERA SENHA WEB
CFG-WEBADMINISTRATOR::OLTID=ip_olt,PONID=1-1-1-5,ONUIDTYPE=LOID,ONUID=FHTT99999999:CTAG::WEBUSERNAME=admin,WEBPASSWORD=senha;

# VERIFICA SINAL DA ONU
LST-OMDDM::OLTID=ip_olt,PONID=1-1-1-5,ONUIDTYPE=LOID,ONUID=FHTT99999999:CTAG::;

# VERIFICA STATUS DAS PORTAS LAN
LST-ONULANINFO::OLTID=ip_olt,PONID=1-1-1-5,ONUIDTYPE=LOID,ONUID=FHTT99999999,PORTID=NA-NA-NA-1:CTAG::;

# VERIFICA STATUS DA WAN
LST-ONUWANSERVICECFG::OLTID=ip_olt,PONID=1-1-1-5,ONUIDTYPE=LOID,ONUID=FHTT99999999:CTAG::;

# LISTA ONUS AGUARDANDO AUTORIZACAO
LST-UNREGONU::OLTID=ip_olt,PONID=1-1-1-5:CTAG::;

LOGIN:::CTAG::UN=1,PWD=acdmd2018snR;
LST-BRDINFO::OLTID=10.0.2.234:CTAG::;
LST-ONU::OLTID=10.0.2.234:CTAG::;
LST-ONULANINFO::OLTID=10.0.2.234,PONID=1-1-1-1,ONUIDTYPE=AN5506-04-B2,ONUID=FHTT041CCF20,PORTID=NA-NA-NA-1:CTAG::;
LST-LANCAR::OLTID=10.0.2.234,PONID=1-1-1-1,ONUIDTYPE=AN5506-04-B2,ONUID=FHTT041CCF20,ONUPORT=NA-NA-NA-1:CTAG::;
LOGOUT:::CTAG::;

*/

class FiberHome
{
	private $fp;
	private $ipTL1;
	private $DEBUG;

	function __construct($ipAdmin, $ipTL1, $User, $Pass, $debug=false)
	{
		$this->fp = fsockopen($ipAdmin, 3337, $errno, $errstr, 30);
		if (!$this->fp) {
			die("$errstr ($errno)\n");
		} else {
			$this->cmd('LOGIN:::CTAG::UN={$User},PWD={$Pass};');
			$this->ipTL1 = $ipTL1;
		}
		$hist->DEBUG = $debug;
	}

	public function cmd($cmd)
	{
		$ret = array();

		$this->msg($cmd);
		fwrite($this->fp, "$cmd\n");
		while (true) {
			$c = fread($this->fp, 1);
			if ($c == ';') break;
			$lin = trim($c . fgets($this->fp));
			$ret[] = split("\t", $lin);
			$this->msg($lin);
		}
		return $ret;
	}

	private function msg($msg)
	{
		if ($this->DEBUG) {
			echo trim($msg)."\n";
		}
	}

	private function ordenaONUs($a, $b)
	{
		return strcasecmp($a['NAME'], $b['NAME']);
	}

	public function listONUs()
	{
		$ret = array();

		$list = $this->cmd("LST-ONU::OLTID={$this->ipTL1}:CTAG::;");
		if (preg_match("/block_records\=(\d+)/", $list[5][0], $match)) {
			$nONUs = $match[1];
			$header = $list[9];
			for ($n=0; $n<$nONUs; $n++) {
				for ($c=0; $c<count($header); $c++) {
					$mac = $list[10+$n][8];
					$ret[$mac][strtoupper($header[$c])] = $list[10+$n][$c];
				}
//				if (preg_match($ret[$mac]['NAME'], "/^(\w+)/", $match)) {
//					$ret[$mac]['NAME'] = $match[1];
//				}
			}
//			uksort($ret, array($this, 'ordenaONUs'));
		}
		return $ret;
	}

	public function ONUStates(&$onuArr)
	{
		foreach ($onuArr as $mac => $reg) {
			$list = $this->cmd("LST-ONUSTATE::OLTID={$this->ipTL1},PONID={$reg['PONID']},ONUIDTYPE=MAC,ONUID={$mac}:CTAG::;");
			$header = $list[9];
			for ($c=0; $c<count($header); $c++) {
				$onuArr[$mac][strtoupper($header[$c])] = $list[10][$c];
			}
		}
		return $onuArr;
	}

	public function ONUInfos(&$onuArr)
	{
		foreach ($onuArr as $mac => $reg) {
			$list = $this->cmd("LST-OMDDM::OLTID={$this->ipTL1},PONID={$reg['PONID']},ONUIDTYPE=MAC,ONUID={$mac}:CTAG::;");
			$header = $list[9];
			for ($c=0; $c<count($header); $c++) {
				$onuArr[$mac][strtoupper($header[$c])] = $list[10][$c];
			}
		}
		return $onuArr;
	}

	public function ONUOrfas(&$onuArr)
	{
		$list = $this->cmd("LST-UNREGONU::OLTID={$this->ipTL1}:CTAG::;");
		var_dump($list);
	}
}

?>
