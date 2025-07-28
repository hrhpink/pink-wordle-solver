<html lang="en">

    <?php

        //Split word file by \n character and store in array dict
        $wordfile = file_get_contents('words.txt');
        $dict = explode("\n", $wordfile);

        //Alphabet used to generate regex prompt
        $alphabet =  array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z");

        //Variables used to restore appearance of page after refresh
        $refreshExact = ['unchecked', 'unchecked', 'unchecked', 'unchecked', 'unchecked'];
        $refreshGuess = ["", "", "", "",""];
        $refreshExclude = ["A"=>'unchecked', "B"=>'unchecked', "C"=>'unchecked', "D"=>'unchecked', "E"=>'unchecked', "F"=>'unchecked', "G"=>'unchecked', "H"=>'unchecked', "I"=>'unchecked', "J"=>'unchecked', "K"=>'unchecked', "L"=>'unchecked', "M"=>'unchecked',"N"=>'unchecked', "O"=>'unchecked',"P"=>'unchecked', "Q"=>'unchecked', "R"=>'unchecked', "S"=>'unchecked', "T"=>'unchecked',"U"=>'unchecked', "V"=>'unchecked', "W"=>'unchecked', "X"=>'unchecked', "Y"=>'unchecked', "Z"=>'unchecked'];
        $curAttempt = 1; //tracks attempts for previous guess table

        function regexAnywhere($currGuess, $knownSpots, $regexString, $outputRegex){
            for($i=0; $i<5;$i++){
                if($currGuess[$i]!= "" & ($currGuess[$i] != $knownSpots[$i])){
                    $outputRegex = $outputRegex . "(?=[";
                    $outputRegex = $outputRegex . $regexString;
                    $outputRegex = $outputRegex . "]";
                    $outputRegex = $outputRegex . "*";
                    $outputRegex = $outputRegex . $currGuess[$i];
                    $outputRegex = $outputRegex . "[";
                    $outputRegex = $outputRegex . $regexString;
                    $outputRegex = $outputRegex . "]*)";
                }
            }
            return $outputRegex;
        }

        function regexExact($outputRegex, $currGuess, $knownSpots, $regexString){

            $outputRegex = $outputRegex. "(";

                for($i=0; $i<5; $i++){
                    if($i==0){
                        if(($currGuess[$i] != "") &&($knownSpots[$i] == $currGuess[$i])){
                            $outputRegex = $outputRegex . "^";
                            $outputRegex = $outputRegex . $currGuess[$i];
                        }
                        else{
                            $outputRegex = $outputRegex . "[";
                            $outputRegex = $outputRegex . $regexString;
                            $outputRegex = $outputRegex . "]";
                        }
                    }
                    else if (($knownSpots[$i] == $currGuess[$i])&& $knownSpots[$i]!=""){
                        $outputRegex = $outputRegex . $knownSpots[$i];
                    }
                    else{
                        $outputRegex = $outputRegex . "[";
                        $outputRegex = $outputRegex . $regexString;
                        $outputRegex = $outputRegex . "]";
                    }
                }
                $outputRegex = $outputRegex . ")";

                return $outputRegex;
        }

        //Start session, reset when "Reset Solve Assistant" is clicked
        session_start();
        if (isset($_POST['SolverReset'])) {
            session_unset();
        }

        //CLASS USED WITH PHP OBJECT for printing each guess and its exact letters
        class SessionHistory{
            public $attempt = "1"; //Tracks current attempt
            public $guessList = ["","","","",""]; //Stores current guess

            //Print each letter of the given guess and store in a cell of the previous guess table
            function printGuess(){
                echo "<td>";
                foreach ($this->guessList as $l){
                    print_r(" $l ");
                }
                echo "</td><td>";
            }

            //Check to see which checkboxes were checked for the exact letters and print the corresponding letter of the guess
            function printExact(){
                if(isset($_SESSION["exactList"])){

                    foreach ($_SESSION["exactList"][$this->attempt-1] as $index=>$value){
                        if($value=="on"){

                            switch ($index){
                                case 0:
                                    echo $this->guessList[0];
                                    echo " ";
                                    break;
                                case 1:
                                    echo $this->guessList[1];
                                    echo " ";
                                    break;
                                case 2:
                                    echo $this->guessList[2];
                                    echo " ";
                                    break;
                                case 3:
                                    echo $this->guessList[3];
                                    echo " ";
                                    break;
                                case 4:
                                    echo $this->guessList[4];
                                    echo " ";
                                    break;
                                default:
                                print "Error.";
                                break;                     
                            }
                        }

                        }
                }
                    
                echo "</td></tr>";
            }
        }

    ?>

    <head>
        <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.3/dist/jquery.min.js"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/semantic-ui@2.5.0/dist/semantic.min.css">
        <script src="https://cdn.jsdelivr.net/npm/semantic-ui@2.5.0/dist/semantic.min.js"></script>
        <link rel="stylesheet" href="word.css">
        <link rel="stylesheet" href="chrome-extension://ihcjicgdanjaechkgeegckofjjedodee/app/content-style.css">
    </head>

    <body data-new-gr-c-s-check-loaded="14.1223.0" data-gr-ext-installed="" style="background-color:black; width:825px; margin:auto">
        &lt; class="ui segment inverted black center aligned"&gt;

            <div class="ui attached segment center aligned inverted black;">
                <h1>Word(le) Solving Assistant </h1>
            </div>
                <!--Script that converts input to capital letter and auto switches to next input box-->
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const inputs = document.querySelectorAll('input');
                        for (let i = 0; i < inputs.length; i++) {
                            inputs[i].addEventListener('keypress', (event) => {
                                // Only use lower case....
                                let lc = event.key.toUpperCase();
                                event.target.value = event.target.value.toLowerCase();
                                event.preventDefault();
                                event.target.value = lc;
                                // Rotate focus to next input or back to start...
                                let nextIndex = event.target.tabIndex + 1;
                                if (nextIndex > 5) nextIndex = 1;
                                let nextInput = document.querySelectorAll('[tabIndex="' + nextIndex + '"]');
                                nextInput[0].focus();
                            });
                        }
                    });
                </script>
            <div class="ui segment inverted grey left aligned">
                Enter letters which matched in the 5 blanks provided.<br>
                Place exact matches in their correct position, and toggle on the checkbox below them.<br>
                Remote select any letters which <i>cannot</i> be used in the word below.<br>
            </div>
        <form id="nextGuess" method="POST" action="">
            <table style="font-size:45px; margin:auto; width:200px">
                <?php 
                //If "Check Word" is clicked
                if (isset($_POST['check'])){

                    //Add guess to session list of all guesses
                    $_SESSION["guessList"][]=$_POST['guess'];

                    //Set the current guess to whatever is at the end of the session guess list
                    $refreshGuess = end($_SESSION["guessList"]);

                    //If any exact matches are marked
                    if(isset($_POST['exact'])){
                        $refreshExact = ['unchecked', 'unchecked', 'unchecked', 'unchecked', 'unchecked']; //Stores states of all checkboxes
                        $_SESSION["exactList"][]=$_POST['exact']; //Adds list of exact checkboxes to session array of all exact selections
                        print "Exact added for real!";
                        $endExactSession = end($_SESSION["exactList"]); //Set current exact mathces to whatever is at the end of the session exact list
                        
                        //Since only the checked boxes are stored in the exactList, some indexes can be skipped. This makes sure that only those indexes that were POSTed
                        //are changed to "checked" for the current exact array.
                        foreach ($endExactSession as $index=>$value){
                            switch ($index){
                                case 0:
                                    $refreshExact[0]='checked';
                                    break;
                                case 1:
                                    $refreshExact[1]='checked';
                                    break;
                                case 2:
                                    $refreshExact[2]='checked';
                                    break;
                                case 3:
                                    $refreshExact[3]='checked';
                                    break;
                                case 4:
                                    $refreshExact[4]='checked';
                                    break;
                                default:
                                print "Error.";
                                break;                     
                            }
                        }
                    }
                    //If any exclusions are posted
                    if (isset($_POST['exclude'])){

                        //Establish array that holds check values of all letters
                        $refreshExclude = ["A"=>'unchecked', "B"=>'unchecked', "C"=>'unchecked', "D"=>'unchecked', "E"=>'unchecked', "F"=>'unchecked', "G"=>'unchecked', "H"=>'unchecked', "I"=>'unchecked', "J"=>'unchecked', "K"=>'unchecked', "L"=>'unchecked', "M"=>'unchecked',"N"=>'unchecked', "O"=>'unchecked',"P"=>'unchecked', "Q"=>'unchecked', "R"=>'unchecked', "S"=>'unchecked', "T"=>'unchecked',"U"=>'unchecked', "V"=>'unchecked', "W"=>'unchecked', "X"=>'unchecked', "Y"=>'unchecked', "Z"=>'unchecked'];
                        $_SESSION["excludeList"][]=$_POST['exclude']; //Add current list of exclusions to session list for exclusions
                        $endExcludeSession = end($_SESSION["excludeList"]); //Set whatever is at the end of the exclusion list as the current exclusion list

                        //Iterate through each letter of the alphabet and check if it equals the key (e.g. A, B, C) of an entry in the exclusion array. If so, set that index of refreshExclude
                        //to be "checked"
                        foreach($alphabet as $letter){
                       
                            foreach ($endExcludeSession as $excIndex=>$excStatus){
                                
                                    if($letter == $excIndex){
                                        $refreshExclude[$letter]='checked';
                                        
                                        break;
                                    }   
                            }
                        }
                    }
                }
                else{
                    $refreshGuess = ["", "", "", "",""]; //if button wasn't checked, just set currentGuess to empty          
                }
                ?>
                <!--Table for guess letters and exact match checkboxes-->
                <tbody >
                    <tr>
                        <td> <input class="ng input" name="guess[0]" style="background-color:black; border-color:white; color: hsl(313, 100.00%, 61.00%); font-family:impact; text-align:center;" maxlength="1" size="1" tabindex="1" value="<?php echo $refreshGuess[0]; ?>"></td>
                        <td> <input class="ng input" name="guess[1]" style="background-color:black; border-color:white; color: hsl(313, 100.00%, 61.00%); font-family:impact; text-align:center;" maxlength="1" size="1" tabindex="2" value="<?php echo $refreshGuess[1]; ?>"></td>
                        <td> <input class="ng input" name="guess[2]" style="background-color:black; border-color:white; color: hsl(313, 100.00%, 61.00%); font-family:impact; text-align:center;" maxlength="1" size="1" tabindex="3" value="<?php echo $refreshGuess[2]; ?>"></td>
                        <td> <input class="ng input" name="guess[3]" style="background-color:black; border-color:white; color: hsl(313, 100.00%, 61.00%); font-family:impact; text-align:center;" maxlength="1" size="1" tabindex="4" value="<?php echo $refreshGuess[3]; ?>"></td>
                        <td> <input class="ng input" name="guess[4]" style="background-color:black; border-color:white; color: hsl(313, 100.00%, 61.00%); font-family:impact; text-align:center;" maxlength="1" size="1" tabindex="5" value="<?php echo $refreshGuess[4]; ?>"></td>
                    </tr>
                    <tr>
                        <td> <input class="ui checkbox" type="checkbox" name="exact[0]" <?php echo ((isset($_POST['exact']))&&($refreshExact[0]=='checked'))? 'checked':''  ?>></td>
                        <td> <input class="ui checkbox" type="checkbox" name="exact[1]" <?php echo ((isset($_POST['exact']))&&($refreshExact[1]=='checked'))? 'checked':''  ?>></td>
                        <td> <input class="ui checkbox" type="checkbox" name="exact[2]" <?php echo ((isset($_POST['exact']))&&($refreshExact[2]=='checked'))? 'checked':''  ?>></td>
                        <td> <input class="ui checkbox" type="checkbox" name="exact[3]" <?php echo ((isset($_POST['exact']))&&($refreshExact[3]=='checked'))? 'checked':''  ?>></td>
                        <td> <input class="ui checkbox" type="checkbox" name="exact[4]" <?php echo ((isset($_POST['exact']))&&($refreshExact[4]=='checked'))? 'checked':''  ?>></td>
                    </tr>
                </tbody>
            </table>

        <!--All the elements for checkboxes for letter exclusion-->
        <div class=" ui segment inverted">
            <div class="ui label attached inverted grey large top left">Letters to Exclude</div>
            <div class="ui horizontal segments" style="text-align:center; font-weight:bold; font-family:verdana;">
                <div class="ui segment letter inverted " style="background-color:black; border-color:white;">A<br><input type="checkbox" name="exclude[A]" <?php echo ((isset($_POST['exclude']))&&($refreshExclude["A"]=='checked'))? 'checked':''  ?>></div>
                <div class="ui segment letter inverted " style="background-color:black; border-color:white;">B<br><input type="checkbox" name="exclude[B]" <?php echo ((isset($_POST['exclude']))&&($refreshExclude["B"]=='checked'))? 'checked':''  ?>></div>
                <div class="ui segment letter inverted " style="background-color:black; border-color:white">C<br><input type="checkbox" name="exclude[C]" <?php echo ((isset($_POST['exclude']))&&($refreshExclude["C"]=='checked'))? 'checked':''  ?>></div>
                <div class="ui segment letter inverted " style="background-color:black; border-color:white">D<br><input type="checkbox" name="exclude[D]" <?php echo ((isset($_POST['exclude']))&&($refreshExclude["D"]=='checked'))? 'checked':''  ?>></div>
                <div class="ui segment letter inverted " style="background-color:black; border-color:white">E<br><input type="checkbox" name="exclude[E]" <?php echo ((isset($_POST['exclude']))&&($refreshExclude["E"]=='checked'))? 'checked':''  ?>></div>
                <div class="ui segment letter inverted " style="background-color:black; border-color:white">F<br><input type="checkbox" name="exclude[F]" <?php echo ((isset($_POST['exclude']))&&($refreshExclude["F"]=='checked'))? 'checked':''  ?>></div>
                <div class="ui segment letter inverted " style="background-color:black; border-color:white">G<br><input type="checkbox" name="exclude[G]" <?php echo ((isset($_POST['exclude']))&&($refreshExclude["G"]=='checked'))? 'checked':''  ?>></div>
                <div class="ui segment letter inverted " style="background-color:black; border-color:white">H<br><input type="checkbox" name="exclude[H]" <?php echo ((isset($_POST['exclude']))&&($refreshExclude["H"]=='checked'))? 'checked':''  ?>></div>
                <div class="ui segment letter inverted " style="background-color:black; border-color:white">I<br><input type="checkbox" name="exclude[I]" <?php echo ((isset($_POST['exclude']))&&($refreshExclude["I"]=='checked'))? 'checked':''  ?>></div>
                <div class="ui segment letter inverted " style="background-color:black; border-color:white">J<br><input type="checkbox" name="exclude[J]" <?php echo ((isset($_POST['exclude']))&&($refreshExclude["J"]=='checked'))? 'checked':''  ?>></div>
                <div class="ui segment letter inverted " style="background-color:black; border-color:white">K<br><input type="checkbox" name="exclude[K]" <?php echo ((isset($_POST['exclude']))&&($refreshExclude["K"]=='checked'))? 'checked':''  ?>></div>
                <div class="ui segment letter inverted " style="background-color:black; border-color:white">L<br><input type="checkbox" name="exclude[L]" <?php echo ((isset($_POST['exclude']))&&($refreshExclude["L"]=='checked'))? 'checked':''  ?>></div>
                <div class="ui segment letter inverted " style="background-color:black; border-color:white">M<br><input type="checkbox" name="exclude[M]" <?php echo ((isset($_POST['exclude']))&&($refreshExclude["M"]=='checked'))? 'checked':''  ?>></div>
            </div>
            <div class="ui horizontal segments" style="text-align:center; font-weight:bold; font-family:verdana">
                <div class="ui segment letter inverted " style="background-color:black; border-color:white">N<br><input type="checkbox" name="exclude[N]" <?php echo ((isset($_POST['exclude']))&&($refreshExclude["N"]=='checked'))? 'checked':''  ?>></div>
                <div class="ui segment letter inverted " style="background-color:black; border-color:white">O<br><input type="checkbox" name="exclude[O]" <?php echo ((isset($_POST['exclude']))&&($refreshExclude["O"]=='checked'))? 'checked':''  ?>></div>
                <div class="ui segment letter inverted " style="background-color:black; border-color:white">P<br><input type="checkbox" name="exclude[P]" <?php echo ((isset($_POST['exclude']))&&($refreshExclude["P"]=='checked'))? 'checked':''  ?>></div>
                <div class="ui segment letter inverted " style="background-color:black; border-color:white">Q<br><input type="checkbox" name="exclude[Q]" <?php echo ((isset($_POST['exclude']))&&($refreshExclude["Q"]=='checked'))? 'checked':''  ?>></div>
                <div class="ui segment letter inverted " style="background-color:black; border-color:white">R<br><input type="checkbox" name="exclude[R]" <?php echo ((isset($_POST['exclude']))&&($refreshExclude["R"]=='checked'))? 'checked':''  ?>></div>
                <div class="ui segment letter inverted " style="background-color:black; border-color:white">S<br><input type="checkbox" name="exclude[S]" <?php echo ((isset($_POST['exclude']))&&($refreshExclude["S"]=='checked'))? 'checked':''  ?>></div>
                <div class="ui segment letter inverted " style="background-color:black; border-color:white">T<br><input type="checkbox" name="exclude[T]" <?php echo ((isset($_POST['exclude']))&&($refreshExclude["T"]=='checked'))? 'checked':''  ?>></div>
                <div class="ui segment letter inverted " style="background-color:black; border-color:white">U<br><input type="checkbox" name="exclude[U]" <?php echo ((isset($_POST['exclude']))&&($refreshExclude["U"]=='checked'))? 'checked':''  ?>></div>
                <div class="ui segment letter inverted " style="background-color:black; border-color:white">V<br><input type="checkbox" name="exclude[V]" <?php echo ((isset($_POST['exclude']))&&($refreshExclude["V"]=='checked'))? 'checked':''  ?>></div>
                <div class="ui segment letter inverted " style="background-color:black; border-color:white">W<br><input type="checkbox" name="exclude[W]" <?php echo ((isset($_POST['exclude']))&&($refreshExclude["W"]=='checked'))? 'checked':''  ?>></div>
                <div class="ui segment letter inverted " style="background-color:black; border-color:white">X<br><input type="checkbox" name="exclude[X]" <?php echo ((isset($_POST['exclude']))&&($refreshExclude["X"]=='checked'))? 'checked':''  ?>></div>
                <div class="ui segment letter inverted " style="background-color:black; border-color:white">Y<br><input type="checkbox" name="exclude[Y]" <?php echo ((isset($_POST['exclude']))&&($refreshExclude["Y"]=='checked'))? 'checked':''  ?>></div>
                <div class="ui segment letter inverted " style="background-color:black; border-color:white">Z<br><input type="checkbox" name="exclude[Z]" <?php echo ((isset($_POST['exclude']))&&($refreshExclude["Z"]=='checked'))? 'checked':''  ?>></div>
            </div>
            <!--Primary code for input formatting, regex formatting, and testing against the dictionary for results-->
            <?php
                //Initializing base case arrays
                $currGuess = array("","","","",""); //submitted letters
                $exactGuess = array("off","off","off","off","off"); //which letters were checked as exact matches (on/off)
                $outputRegex=""; //Stores the regex to be greped

                //When check button pressed, begin processing for regex
                if (isset($_POST['check']) && ($outputRegex=="")){

                    //Save session variables
                    $_SESSION["list"][]=$_POST['guess'];

                    //Store submitted guess into currGuess
                    $currGuess = $_POST['guess'];

                    //If any exact matches were marked, begin specific processing for regex
                    if(isset($_POST['exact'])){ 

                        //Iterate through values that were marked and extract values for storage in designated index within exactGuess
                        foreach ($_POST['exact'] as $index=>$value){
                            switch ($index){
                                case 0:
                                    $exactGuess[0]=$value;
                                    break;
                                case 1:
                                    $exactGuess[1]=$value;
                                    break;
                                case 2:
                                    $exactGuess[2]=$value;
                                    break;
                                case 3:
                                    $exactGuess[3]=$value;
                                    break;
                                case 4:
                                    $exactGuess[4]=$value;
                                    break;
                                default:
                                print "Error.";
                                break;                     
                            }
                        }
                    }
                    
                    //Initialize array that stores known spots for letters
                    $knownSpots= array("","","","","");
                    $index = 0;

                    //Convert exactGuess on/off values to the actual letter and store in knownSpots
                    foreach ($currGuess as $letterGuess){
                        if (($exactGuess[$index] == "on") && $index < 5){
                            $knownSpots[$index] = $letterGuess;
                        }
                        $index++;
                    }

                    $regexString = ""; //eventually, the concatenated alphabet, minus any letters that were excluded
                    $match = false; //used to track whether the current selected letter of alphabet matches one of the excluded letters
                    $numExclusions = 0; //counts how many letters were excluded
                    $outputRegex = ""; //eventually, the regex to be submitted to search for words
                    $spotCheck = 0; //if there are any exact letters, spotCheck=1, otherwise spotCheck=0
                    $i = 0; //loop variable

                    $count = 0; //loop variable to check for whether the end of the exclusion array has been reached

                    //Used to put blank values in previous guess table
                    if(!isset($_POST['exact'])){
                        $_SESSION["exactList"][]=["0"=>"off", "1"=>"off", "2"=>"off", "3"=>"off","4"=>"off"]; 
                    }

                    //If any exclusions were marked, begin additional processing for regex
                    if(isset($_POST['exclude'])){

                        $flag=0; //checks for whether outputregex is already formatted-- used for edge case where exclusions marked but no guess

                        //Count number of exclusions in array
                        foreach($_POST['exclude'] as $excIndex=>$excStatus ){
                            $numExclusions++;
                        }
                        //Matches each letter of the alphabet to check whether it is an exclusion. If letter is not excluded, add it to the regexString of allowed letters to search
                        foreach($alphabet as $letter){
                            $count=0;
                            foreach ($_POST['exclude'] as $excIndex=>$excStatus){
                                
                                    if($letter == $excIndex){
                                        $match = true;
                                        $count++;
                                        break;
                                    }else{ $match = false; $count++;}

                                    if($match == false && ($count == $numExclusions)){
                                        $regexString = $regexString . $letter;
                                    }     
                            }
                        }

                        //Check for non-null values in the array for exact matches
                        for($i=0; $i < 5; $i++){
                            if ($knownSpots[$i] != ""){
                                $spotCheck = 1;
                                break;
                            }
                        }
                        //For any letter that is part of the word but NOT an exact match, print the regex for it
                        $outputRegex = regexAnywhere($currGuess, $knownSpots, $regexString, $outputRegex);
                        if($outputRegex != ""){
                            $flag = 1;
                        }
                        //If there are any exact matches, generate the regex for the word
                        if ($spotCheck == 1){
                            $outputRegex = regexExact($outputRegex, $currGuess, $knownSpots, $regexString);
                            $flag=1;
                        }else if ($flag==1){
                            $outputRegex = $outputRegex . "(^" . "[" . $regexString . "]" . "[" . $regexString . "]" . "[" . $regexString . "]" . "[" . $regexString . "]" . "[" . $regexString . "]" .")";
                        }
                        //If ONLY exclusions marked, search for those words
                        if($flag==0){
                            $outputRegex = "(^" . "[" . $regexString . "]" . "[" . $regexString . "]" . "[" . $regexString . "]" . "[" . $regexString . "]" . "[" . $regexString . "]" .")";
                        }
                    }
                    //If NO exclusions were marked, proceed to specified regex processing
                    else
                    {
                        //Concatenate entire alphabet into regexString
                        foreach($alphabet as $letter){

                            $regexString = $regexString . $letter;
                        }     
                        $outputRegex = regexAnywhere($currGuess, $knownSpots, $regexString, $outputRegex);
                        //Check for non-null values in the array for exact matches
                        for($i=0; $i < 5; $i++){
                            if ($knownSpots[$i] != ""){
                                $spotCheck = 1;
                                break;
                            }
                        }
                        //If there are any exact matches, generate the regex for the word
                        if ($spotCheck == 1){
                            $outputRegex = regexExact($outputRegex, $currGuess, $knownSpots, $regexString);
                        }
                        else{
                            $outputRegex = $outputRegex . "(^";
                            for($i=0;$i<5;$i++){
                                $outputRegex = $outputRegex . "[";
                                $outputRegex = $outputRegex . $regexString;
                                $outputRegex = $outputRegex . "]";
                            }
                            $outputRegex = $outputRegex . ")";
                        }                       
                    }
                    
                    $outputRegex = strtolower($outputRegex);

                    $numMatches=0;
                    $inputRegex = "/" . $outputRegex . "/";
                    $grepmatches = preg_grep($inputRegex, $dict);
                    $matches  = [];
                    $matchIndex=0;
                    foreach ($grepmatches as $key => $value){
                        $matches[$matchIndex] = $value;
                        $matchIndex++;
                    }
                    $numMatches=count($matches);
                ?> 
        </div>
        <div class="ui segment inverted">
             <div class="ui label attached inverted grey large"><?=$numMatches?> possible matching words.</div>
                <table class='ui inverted pink large celled striped fixed table'>

                    <!--Print match table-->
                    <?php

                        $matchIndex = 0;

                        if ($outputRegex != ""){
                            while ($matchIndex < $numMatches){
                                echo '<tr>';
                                    for($i=0;$i<8;$i++){
                                        if($matchIndex<$numMatches){
                                            echo '<td>';
                                            echo $matches[$matchIndex];
                                            echo '</td>';
                                            $matchIndex++;
                                        }
                                        else{
                                            echo "<td></td>";
                                            if($i==7){
                                                break;
                                            }
                                        }

                                    }
                                echo '</tr>';
                                    
                            }
                        }                              

                    ?>
                </table>
                <?php 
                        }
                    else{
                ?>
                <!--Default message before check is clicked-->
                <div class="ui " id="nm">No matching words found.</div>
                    <?php 
                    }
                    ?>
                </div>
            </div>

            <!--Design logic for buttons-->
            <div class="ui segment inverted">
                <div class="ui horizontal segments">
                    <!--Submit-->
                    <div class="ui segment inverted black align right">
                        <button class="ui button centered" type="submit" name="check">Check Word</button>
                    </div>
                    <!--Reset solver-->
                    <div class="ui segment inverted black align left">
                        <button type="submit" name="SolverReset" valu="yes" class="ui button small centered">Reset Solve Assistant</button>
                    </div>
                </div>
            </div>
            <!--Debugging Info-->
            <div class="ui segment inverted">
                <div class="ui label attached top inverted large grey">Debugging Info</div>
                    <div class="ui segment inverted black" style="border-style:solid; border-color: transparent transparent yellow transparent">
                        Using regex:
                        <?php 
                                if (isset($_POST['check'])&&(isset($_POST['exclude']) || isset($_POST['guess']))){
                                    
                            ?>
                        <span id="regex" style="color:yellow"><?= $outputRegex ?></span>
                        <?php
                                }
                        ?>

                        </div>
                    <div class="ui segment inverted black" style="border-style:solid; border-color: transparent transparent yellow transparent">
                        Dictionary Size: 
                        <span id="dictSize" style="color:yellow"><?= count($dict)?></span>
                    </div>  
                </div>  
            </div>
            <!--PREVIOUS ATTEMPTS USING SESSION-->
            <div class="ui inverted segment">
                <?php if(isset($_POST['check'])){?>
                <div class="ui label attached inverted grey large"> Previous guesses in the session:</div>
                <table class="ui inverted blue celled striped table">
                    <thead>
                        <tr>
                            <th>Attempt</th>
                            <th>Guess</th>
                            <th>Exact Matches</th>
                        </tr>
                    </thead>
                    <?php
                    //If any guesses have been made in the current session, print it + the exact letter matches
                    if (isset($_SESSION["guessList"])){

                        foreach($_SESSION["guessList"] as $guessArray){
                            echo "<tr><td>";
                            echo $curAttempt;
                            echo "</td>";
                            $guess =  new SessionHistory();
                            $guess->attempt = $curAttempt;
                            $guess->guessList = $guessArray;
                            $guess->printGuess();
                            $guess->printExact();
                            $curAttempt++;
                        
                        }
                    }else if (!isset($_SESSION['guess'])){
                    
                        echo "<tr><td>";
                        echo $curAttempt;
                        echo "</td><td></td><td></td></tr>";
                    }
                    ?>
                    <?php }?>
                </table>
            </div>  
        </form>
    </body>
</html>