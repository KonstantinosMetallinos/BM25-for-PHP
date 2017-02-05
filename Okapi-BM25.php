<?php 


/*

	This script takes as an input two arrays and returns a sorted array with "key" the original document's key and value the BM25 score of the documents. 
	The code allows for parameter "k1" and "b" alterations as well as turning off parameter check and warnings. 
	The first array is an array of strings with each string representing a query term. (e.g. BM25(array("Single Term", "Not", "a", "single", "term"), array("document1", "document2",...)).    
	The second array is our document collection. The Tokenisation is performed in the code so each document should be in string format as a single element of the docCollection array. (e.g. docCollection = array("string of document 1", "string of document 2", "etc.", ...)).

	Note that the documents and query terms get converted to upper case so the query search is case-insensitive. 
	Note that terms can contain more then one token (e.g. "IT Manager"). 

	Contact @author at metallinos.konstantinos.e@gmail.com
	
*/


 function IDF($N, $nq_i){
 	/*
	$N 	 	Number of documents in our collection.
	$nq_i 	Number of documents that contain query q_i.

	Returns the Inverse Document Frequency (IDF) for given query.
 	*/

 	return log(($N + 0.5 - $nq_i/(0.5 + $nq_i)));

 }


function BM25($Q, $docCollection, $k=1.2, $b=0.75, $paramaterCheck=TRUE, $warningCheck=TRUE){
	/*
	$Q 							A set of queries Q = array(q1, q2, ..., qn).
	$docCollection				Collection of all documents we wish to score; docCollection = array("String of doc1", "String of doc2", ...)
	$k 							Tuning paramerter. Recommended values between 1.2 and 2.
	$b   						Tuning parameeter. Recommended value 0.75. For b=1 we get BM11 and b=0 we get BM15.
	$paramaterCheck 			Check if user input correctly given. 
	$warningCheck 				Allow warning pop-ups when running the code.

	$N 							Number of Documents in our collection.
	$NumOfQueries 				Number of terms our Query "Q" has.
	$queryFrequencyPerDocument	Frequency at which query term q_i appears in our document collection. 
	$scoredDocuments 			The array which the scores of each Document D will be stored.
	$wordsindoc					Number of words (tokens) contained in each document. 
	$documentCount				Number of documents our document collection contains.
	$avgld						Average Document Length. 
	$nq_i	 					Number of times token q_i appears in our collection of documents.
	$individualScore			The score of the current document D.
	$q_i 						Term "i" from our Query Q.
	$score 						Array containing the score for each document.
	$spamflag 					Used to warn the user if an empty query token has been provided (e.g. space has been given as a token).

	Returns a sorted array of the BM25 score of each document.
	*/

	if($paramaterCheck=TRUE){
		if(!is_array($Q)){
			die('Error: Parameter $Q is expected to be an array. Non-array element given.');
		} 
		if(!is_array($docCollection)){
			die('Error: Parameter $docCollection is expected to be an array. Non-array element given.');
		}
	}

	$Q = array_map('strtoupper', $Q);

	$N = count($docCollection);
	$NumOfQueries = count($Q);
	$queryFrequencyPerDocument = array();
	$scoredDocuments = array();
	$wordsindoc = array();
	// Boolean variable to stop program spamming a warning message. 
	$spamflag = FALSE;
	// Initialse array.
	$nq_i = array_fill(0, $NumOfQueries, 0);

	for($documentCount=0; $documentCount < $N; $documentCount++) { 

		// Tokenise the document
		$TokeniseddocCollection[$documentCount] = explode(" ", str_replace(array('.', ',', "\t", '!', '\"', '£', '$', '%', '^', '&', '*', '(', ')', '-', '_', '=', '{', '}', '[', ']', ';', ':', '@', '~', '<', '>', '?', '/', '\\', '\`', '¬', "\n","\r"), ' ', $docCollection[$documentCount]));

		// Find the number of tokens (words) that exist in the document. This will also give us the average length of documents. 
		$wordsindoc[$documentCount] = count($TokeniseddocCollection[$documentCount]);

		// Create a matrix where the rows are the documents and the columns are the query terms. The value of elements (i,j) represents the number of times document i contains query j. 
		for($queryCount=0; $queryCount < $NumOfQueries; $queryCount++){

			$tokenisedQuery = explode(" ", $Q[$queryCount]);

			if(count($tokenisedQuery) == 1 && $tokenisedQuery[0] != ""){ // If query term has 1 token.

				if(in_array($Q[$queryCount], $TokeniseddocCollection[$documentCount]) !== FALSE){
					$queryFrequencyPerDocument[$documentCount][$queryCount] = array_count_values($TokeniseddocCollection[$documentCount])[$Q[$queryCount]];
					$nq_i[$queryCount] ++;
				}else{
					$queryFrequencyPerDocument[$documentCount][$queryCount] = 0;
				}				

			}elseif(count($tokenisedQuery) >= 2){ // If query term has >= 2 tokens. 
				
				if(strpos($docCollection[$documentCount], $Q[$queryCount]) !== FALSE){

			 		$queryFrequencyPerDocument[$documentCount][$queryCount] = 0; 
					$nq_i[$queryCount] ++;
					$lastPos = 0;

					while (($lastPos = strpos($docCollection[$documentCount], $Q[$queryCount], $lastPos))!== false) {
						$queryFrequencyPerDocument[$documentCount][$queryCount] ++; 
					    $lastPos = $lastPos + strlen($Q[$queryCount]);
					}

				}else{
					$queryFrequencyPerDocument[$documentCount][$queryCount] = 0;
				}

			}elseif($spamflag == FALSE && $warningCheck == TRUE){ // If query token is empty give a warning. User might have mistyped something. Otherwise remove to save computation time.
				echo "WARNING: Empty query token detected! Check for mis-types or remove term to speed up the process.</br></br>";
				$spamflag = TRUE;
			}

		}

	}

	for($queryCount=0; $queryCount < $NumOfQueries; $queryCount++){

		if($nq_i[$queryCount] == 0 && $warningCheck == TRUE){
			echo "WARNING: Query token " . ($queryCount+1) . " found no documents containign it. Check for potential spelling mistakes or typos.</br></br>";
		}
	}

	// Compute the average length of our document collection.
	$avgld = array_sum($wordsindoc) / count($wordsindoc);

	// Compute the BM25 score.
	for($documentCount=0; $documentCount < $N; $documentCount++) { 

		$individualScore = 0;

		for($queryCount=0; $queryCount < $NumOfQueries; $queryCount++){

			// If the token is not empty proceed with the computations. If it is empty, essentially you are adding a constant "0" to all documents, skip to save computation time. 
			if($Q[$queryCount] != ""){
				$individualScore += IDF($N, $nq_i[$queryCount]) * $queryFrequencyPerDocument[$documentCount][$queryCount] * ($k + 1) / ($queryFrequencyPerDocument[$documentCount][$queryCount] + $k*(1-$b + $b*$wordsindoc[$documentCount]/$avgld));
			}
		}

		// Store current documents score.
		$score[$documentCount] = $individualScore;

	}

	// Return the scores in a descending order while maintaining their key values so user can distinuish between them. 
	arsort($score);

	return ($score);

}


 ?>