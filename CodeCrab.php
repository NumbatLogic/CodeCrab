<?php
	date_default_timezone_set("UTC");

	define("OPEN_TAG", "/*<?");
	define("CLOSE_TAG", "?>*/");
	
	function Indent($sString, $nIndent)
	{
		$sTemp = "";
		$bNewLine = true;
		$nLength = strlen($sString);
		for ($i = 0; $i < $nLength; $i++)
		{
			if ($bNewLine)
			{
				for ($j = 0; $j < $nIndent; $j++)
					$sTemp .= "\t";
				$bNewLine = false;
			}
			
			$sTemp .= $sString[$i];
				
			if ($sString[$i] == "\n")
				$bNewLine = true;
		}
		
		return $sTemp;
	}

	$g_sParsedFileArray = array();

	$g_sOutputBuffer = "";
	function Output($sOutput)
	{
		global $g_sOutputBuffer;
		$g_sOutputBuffer .= $sOutput;
	}
	
	function O($sOutput)
	{
		Output($sOutput);
	}

	function ParseFile($sFileName)
	{
		echo $sFileName . "\n";

		global $g_sParsedFileArray;
		$sRealPath = realpath($sFileName);
		if (in_array($sRealPath, $g_sParsedFileArray))
		{
			echo "**SKIPPED**\n";
			return;
		}
		$g_sParsedFileArray[] = $sRealPath;

		
		
		
		global $g_sOutputBuffer;
		$g_sOutputBuffer = "";
		
		$sSource = file_get_contents($sFileName);
		$sOutput = "";
		
		$sOldDirectory = getcwd();
		$sNewDirectory = pathinfo($sFileName, PATHINFO_DIRNAME);
		chdir($sNewDirectory);
		
		
		
		
		$sTabBuffer = "";
		$nIndent = 0;
		$nFileSize = strlen($sSource);
		for ($i = 0; $i < $nFileSize; $i++)
		{
			if (substr($sSource, $i, strlen(OPEN_TAG)) == OPEN_TAG)
			{
				$bInline = true;
				$sBlock = "";
				for ($j = $i + strlen(OPEN_TAG); $j < $nFileSize; $j++)
				{
					if (substr($sSource, $j, strlen(CLOSE_TAG)) == CLOSE_TAG)
					{
						$sBlock = substr($sSource, $i, $j - $i + strlen(CLOSE_TAG));
						$i = $j + strlen(CLOSE_TAG) -1;
						break;
					}
					
					if ($sSource[$j] == "\n")
						$bInline = false;
					
					/*if ($sSource[$j] == "\t")
						$nIndent++;
					else
						$nIndent = 0;*/
				}
				
				if ($sBlock != "")
				{
					// output previous... output
					/*if ($g_sOutputBuffer != "")
					{
						$g_sOutputBuffer = Indent($g_sOutputBuffer, $nIndent+1);
						
						$sOutput .= $g_sOutputBuffer;
						if ($g_sOutputBuffer[strlen($g_sOutputBuffer)-1] != "\n")
							$sOutput .= "\n";
						for ($k = 0; $k < $nIndent; $k++)
							$sOutput .= "\t";
					}*/
					
					$sOutput .= $sTabBuffer . $sBlock;
					$sTabBuffer = "";
					
					$g_sOutputBuffer = "";
					eval(substr($sBlock, strlen(OPEN_TAG), strlen($sBlock) - strlen(OPEN_TAG) - strlen(CLOSE_TAG)));
					
					
					if ($g_sOutputBuffer != "")
					{
						if (!$bInline)
						{
							$g_sOutputBuffer = "\n" . Indent($g_sOutputBuffer, $nIndent); //+1
							if ($g_sOutputBuffer[strlen($g_sOutputBuffer)-1] != "\n")
								$g_sOutputBuffer .= "\n";
						}


						$g_sOutputBuffer = str_replace("\n\t\t\t\n", "\n\n", $g_sOutputBuffer);
						$g_sOutputBuffer = str_replace("\n\t\t\n", "\n\n", $g_sOutputBuffer);
						$g_sOutputBuffer = str_replace("\n\t\n", "\n\n", $g_sOutputBuffer);
						
						$sOutput .= $g_sOutputBuffer;
						
						if (!$bInline)
						{
							$nCount = $nIndent;
							if (strpos($sBlock, "Header_Class") !== false) // haxx
								$nCount++;

							for ($k = 0; $k < $nCount; $k++)
								$sOutput .= "\t";
						}
						
						while ($i < $nFileSize && substr($sSource, $i, strlen(OPEN_TAG)) != OPEN_TAG)
							$i++;
						
						if ($i == $nFileSize)
						{
							echo "Error'd: Missing Closing Block: " . $sFileName . "\n";
						}
						else
						{
							$i--;
						}
						
						continue;
					}
					
					continue;
				}
			}
			
			if ($sSource[$i] == "\t")
			{
				$nIndent++;
				$sTabBuffer .= "\t";
				continue;
			}
			else
			{
				$nIndent = 0;
				$sOutput .= $sTabBuffer . $sSource[$i];
				$sTabBuffer = "";
			}	
		}
		
		chdir($sOldDirectory);
		
		if ($sOutput != $sSource)
			file_put_contents($sFileName, $sOutput);
	}

	function ParseDirectory($sDirectory)
	{
		$pDirectory = opendir($sDirectory);
			while($sFile = readdir($pDirectory))
			{
				if ($sFile != "." && $sFile != "..")
				{
					if (is_dir($sDirectory . "/" . $sFile))
					{
						if ($sFile == "ThirdParty")
							continue;
						
						ParseDirectory($sDirectory . "/" . $sFile);
						continue;
					}
					
					$sExtension = strtolower(pathinfo($sFile, PATHINFO_EXTENSION));
					//$sBaseName = 
					
					$sCurrentFile = $sDirectory . "/" . $sFile;
					$sHeaderFile = $sDirectory . "/" . pathinfo($sFile, PATHINFO_FILENAME) . ".h";
					$sSourceFile = $sDirectory . "/" . pathinfo($sFile, PATHINFO_FILENAME) . ".c";
					$sSourceFileCpp = $sDirectory . "/" . pathinfo($sFile, PATHINFO_FILENAME) . ".cpp";
					$sSourceFileNll = $sDirectory . "/" . pathinfo($sFile, PATHINFO_FILENAME) . ".nll";
					
					if ($sCurrentFile == $sHeaderFile)
					{
						ParseFile($sHeaderFile);
						if (file_exists($sSourceFile))
							ParseFile($sSourceFile);
						if (file_exists($sSourceFileCpp))
							ParseFile($sSourceFileCpp);
					}
					else if ($sCurrentFile == $sSourceFile)
					{
						if (!file_exists($sHeaderFile))
							ParseFile($sSourceFile);
					}
					else if ($sCurrentFile == $sSourceFileCpp)
					{
						if (!file_exists($sHeaderFile))
							ParseFile($sSourceFileCpp);
					}
					else if ($sCurrentFile == $sSourceFileNll)
					{
						ParseFile($sSourceFileNll);
					}


					
					//echo $sHeaderFile . "\n";
					//echo $sSourceFile . "\n\n";
					
					//if ($sExtension == "h") // ||*/ $sExtension == "c")
					//{
					//	ParseFile($sHeaderFile);
					//	if (file_exists($sSourceFile))
					//		ParseFile($sSourceFile);
						
						//echo $sHeaderFile . "\n";
						//echo $sSourceFile . "\n\n";
						//echo $sFile . "\n";	
					//}
				}
			}
		closedir($pDirectory);
	}

	$nParameterIndex = 1;

	while ($nParameterIndex < $argc)
	{
		if (strtolower($argv[$nParameterIndex]) == "-p")
		{
			$nParameterIndex++;
			ParseFile($argv[$nParameterIndex]);
			$nParameterIndex++;

			continue;
		}
	
		ParseDirectory($argv[$nParameterIndex]);
		$nParameterIndex++;
	}


/*	$sHeader = file_get_contents($argv[1] . ".h");
	$sSource = file_get_contents($argv[1] . ".cpp");

	$nStart = strpos($sHeader, "/*<Define>") + strlen("/*<Define>");
*///	$nFinish = strpos($sHeader, "</Define>*/", $nStart);
/*
	$sDefine = substr($sHeader, $nStart, $nFinish - $nStart);


	//echo $sDefine . "\n";

	$pJson = json_decode($sDefine, true);
	//var_dump($pJson);

	//var_dump($pJson["memberArray"][0]["name"]);

*/	/*<Define>
		{
			"memberArray": [
				{ "name": "nId", "type": "uint8_t" },
				{ "name": "nType", "type": "uint8_t" },
				{ "name": "nTeam", "type": "uint8_t" },
				{ "name": "nX", "type": "uint8_t" },
				{ "name": "nY", "type": "uint8_t" },
				{ "name": "nDirection", "type": "uint8_t" },
				{ "name": "nHp", "type": "uint8_t" },
				{ "name": "nSpeed", "type": "uint8_t" },
				{ "name": "nMove", "type": "uint8_t" },
				{ "name": "nJump", "type": "uint8_t" },
				{ "name": "nTurnTimer", "type": "uint8_t", "default": "TURN_TIME" },
				{ "name": "bMoved", "type": "bool", "default": "false" },
				{ "name": "bActed", "type": "bool", "default": "false" }
			]
		}
	</Define>*/

	/*<macro !default ; %type% m_%name%>*/
	/*</macro>*/

/*	function Macro($xJson, $sGroupArray, $sSeperator, $sMacro, $nTabDepth)
	{
		$sOutArray = array();

		$nGroupCount = count($sGroupArray);
		for ($g = 0; $g < $nGroupCount; $g++)
		{
			$sGroup = $sGroupArray[$g];

			$xSubJson = $xJson[$sGroup];

			$nCount = count($xSubJson);
			for ($i = 0; $i < $nCount; $i++)
			{
*/				/*if ($sSeperator == ";")
				{
					$sOut .= "\r\n";
					for ($j = 0; $j < $nTabDepth + 1; $j++)
						$sOut .= "\t";
				}*/

/*				$sTemp = $sMacro;

				if ($sSeperator == ";" || $sSeperator == "~")
					$sTemp = str_repeat("\t", $nTabDepth + 1) . $sTemp;
		
				foreach ($xSubJson[$i] as $sName => $sValue)
					$sTemp = str_replace("%" . $sName . "%", $sValue, $sTemp);

				$sOutArray[] = $sTemp;

				//$sOut .= $sTemp;
				
*/				/*if ($sSeperator != ";")
					$sOut .= " " . $sSeperator . " ";
				else
					$sOut .= ";";*/
/*			}
		}

		
		
		$sGlue = " " . $sSeperator . " ";
		if ($sSeperator == ",")
			$sGlue = $sSeperator . " ";
		if ($sSeperator == ";")
			$sGlue = $sSeperator . "\r\n";
		if ($sSeperator == "~")
			$sGlue = "\r\n";

		//$sGlue = 
		$sOut = implode($sGlue, $sOutArray);

		if ($sSeperator == ";")
			$sOut = "\r\n" . $sOut . $sSeperator . "\r\n" . str_repeat("\t", $nTabDepth);

		if ($sSeperator == "~")
			$sOut = "\r\n" . $sOut . "\r\n" . str_repeat("\t", $nTabDepth);



*/		/*if ($sSeperator == ";")
		{
			$sOut .= "\r\n";
			for ($j = 0; $j < $nTabDepth; $j++)
				$sOut .= "\t";
		}*/
/*
		return $sOut;
	}

	function ParseSource($sSource, $pJson)
	{
		$nOffset = 0;
		$sOut = "";
		$nSourceLength = strlen($sSource);

		while ($nOffset < $nSourceLength)
		{
			$nStart = strpos($sSource, "/*<macro ", $nOffset);
			if ($nStart === false)
			{
				$sOut .= substr($sSource, $nOffset);
				$nOffset = $nSourceLength;
				continue;
			}

			$nTabDepth = 0;
			$nSubOffset = $nStart - 1;
			
			while($nSubOffset >= 0 && $sSource[$nSubOffset] == "\t")
			{
				$nTabDepth++;
				$nSubOffset--;
			}
			

			$nStart += strlen("/*<macro ");
*///			$nFinish = strpos($sSource, ">*/", $nStart);
/*
			$sSubStr = substr($sSource, $nStart, $nFinish - $nStart);

			//echo $sSubStr . "\n";
			
			$nSubOffset = strpos($sSubStr, " ");

			$sGroup = substr($sSubStr, 0, $nSubOffset);
			$sGroupArray = explode("+", $sGroup);
			$sSubStr = substr($sSubStr, $nSubOffset + 1);

			$nSubOffset = strpos($sSubStr, " ");
			$sSeperator = substr($sSubStr, 0, $nSubOffset);
			
			$sMacro = substr($sSubStr, $nSubOffset + 1);

*///			$nFinish += strlen(">*/");
/*
			//var_dump($sGroupArray);
			//echo $sGroup . "^" . $sSeperator . "~" . $sMacro . "\n";

			$sOut .= substr($sSource, $nOffset, $nFinish - $nOffset);
			$nOffset = $nFinish;

			$sOut .= Macro($pJson, $sGroupArray, $sSeperator, $sMacro, $nTabDepth);


			
*///			$nStart = strpos($sSource, "/*</macro>*/", $nOffset);
//			$nFinish = $nStart + strlen("/*</macro>*/");
/*

			$sOut .= substr($sSource, $nStart, $nFinish - $nStart);
			$nOffset = $nFinish;
		}

		return $sOut;
	}

	$sHeaderOut = ParseSource($sHeader, $pJson);
	if (strcmp($sHeader, $sHeaderOut) != 0)
		file_put_contents($argv[1] . ".h", $sHeaderOut);

	$sSourceOut = ParseSource($sSource, $pJson);
	if (strcmp($sSource, $sSourceOut) != 0)
		file_put_contents($argv[1] . ".cpp", $sSourceOut);

	return 0;
*/
?>
