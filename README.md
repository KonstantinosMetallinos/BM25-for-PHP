# BM25-for-PHP
Okapi's BM25 model coded in PHP

This script takes as an input two arrays and returns a sorted array with "key" the original document's key and value the BM25 score of the documents. 

The code allows for parameter "k1" and "b" alterations as well as turning off parameter check and warnings. 

The first array is an array of strings with each string representing a query term. (e.g. BM25(array("Single Term", "Not", "a", "single", "term"), array("document1", "document2",...)).   

The second array is our document collection. The Tokenisation is performed in the code so each document should be in string format as a single element of the docCollection array. (e.g. docCollection = array("string of document 1", "string of document 2", "etc.", ...)).

Note that the documents and query terms get converted to upper case so the query search is case-insensitive. 
Note that terms can contain more then one token (e.g. "IT Manager"). 
