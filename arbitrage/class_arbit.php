<?php
//Defining Arbitration class
class Arbitration_usdtbtc
{
    private $trigger_profit; //Above this profit, you trigger Arbitration operations
    private $trigger_marge_security;
    private $exchanges; //Liste of exchanges class available for arbitration operation
    private $order_books_usdtbtc; //Order books of exchanges
    private $date_last_operation; //Timestamp of the previous operation
    private $transfer_duration; //Duration of 3 block's confirmations
    
    //constructeur
    public function  __construct($trigger_profit,$trigger_marge_security) {
        $this->trigger_profit = $trigger_profit;
        $this->trigger_marge_security = $trigger_marge_security;
        $this->date_last_operation = 0;
        $this->transfer_duration = 30*60; //30min for confirming the transfert 
        $this->exchanges = array();
        $this->order_books_usdtbtc = array();
    }
    
    //Get methods
    public function get_trigger_profit(){ return $this->trigger_profit; }
    public function get_trigger_marge_security(){ return $this->trigger_marge_security; }
    public function get_date_last_operation(){ return $this->date_last_operation; }
    public function get_transfer_duration(){ return $this->transfer_duration; }
    public function get_exchanges(){ return $this->exchanges; }
    public function get_order_books_usdtbtc(){ 
        $this->update_order_books();
        return $this->order_books_usdtbtc;
    }
    
    //Set methods
    public function set_trigger_profit($new_trigger_profit){ $this->trigger_profit=$new_trigger_profit;}
    public function set_trigger_marge_security($new_trigger_marge_security){ $this->trigger_marge_security=$new_trigger_marge_security;}
    public function set_date_last_operation($new_date_last_operation){ $this->date_last_operation=$new_date_last_operation;}
    public function set_exchanges($new_exchanges){ $this->exchanges=$new_exchanges;}
    
    //Function to call PDO bdd
    public function appel_bdd(){
        try
        {
            $bdd = new PDO('mysql:host=localhost;dbname=donnees_crypto;charset=utf8', 'root', 'root');
            $bdd->exec("SET CHARACTER SET utf8");
            return $bdd;
        }
        catch(Exception $e)
        {
                die('Erreur : '.$e->getMessage());
        }
    }
    
    //Function to add an operation in the database
    public function inserer_bdd(
        $plateforme_achat,
        $plateforme_vente,
        $delta_p_max,
        $maxQuantiteMax,
        $prix_ordre_vente,
        $prix_ordre_achat,
        $benefice,
        $timestamp,
        $marge){
        //Appel a la bdd
        $bdd = $this->appel_bdd();

        $req=$bdd->prepare('INSERT INTO check_order_books(
        plateforme_achat, 
        plateforme_vente, 
        delta_p_max, 
        quantite_max, 
        prix_ordre_vente, 
        prix_ordre_achat, 
        benefice, 
        timestamp, 
        marge
        ) VALUES(
        :plateforme_achat, 
        :plateforme_vente, 
        :delta_p_max, 
        :quantite_max, 
        :prix_ordre_vente, 
        :prix_ordre_achat, 
        :benefice, 
        :timestamp, 
        :marge
        )');
        $req->execute(array(
            'plateforme_achat'=>$plateforme_achat,
            'plateforme_vente'=>$plateforme_vente, 
            'delta_p_max'=>$delta_p_max, 
            'quantite_max'=>$maxQuantiteMax, 
            'prix_ordre_vente'=>$prix_ordre_vente, 
            'prix_ordre_achat'=>$prix_ordre_achat, 
            'benefice'=>$benefice, 
            'timestamp'=>$timestamp, 
            'marge'=>$marge
        ));
    }
    
    //Function add exchange to exchanges attribute
    function add_exchange($new_exchange){
        $this->exchanges[] = $new_exchange;
    }
    
    //Function that updates order book
    public function update_order_books(){
        $exchanges_class = $this->get_exchanges();
        foreach($exchanges_class as $exch_class){
            $this->order_books_usdtbtc[]=$exch_class->shape_order_book_usdt_btc();
        }
    }
    
    //Defining a profit calculation function
    public function profit_calculation(
        $exch_achat_btc,
        $exch_vente_btc,
        $selling_btc_price, //Low price : we should buy
        $buying_btc_price, //High price : we should sell
        $max_quantity_btc,
        $hors_frais_fixe
    ){
        //Get the required information
        $f_sell = $this->get_exchanges()[$exch_achat_btc]->get_f_sell();
        $f_buy = $this->get_exchanges()[$exch_vente_btc]->get_f_buy();
        $btc_transfert_cost = $this->get_exchanges()[$exch_achat_btc]->get_btc_transfert_cost();
        $usdt_transfert_cost = $this->get_exchanges()[$exch_vente_btc]->get_usdt_transfert_cost();
        
        //Formula detailed in white paper
        if($hors_frais_fixe==0){
            return (1-$f_buy)*$buying_btc_price*$max_quantity_btc - (1-$f_sell)*$selling_btc_price*$max_quantity_btc - $selling_btc_price*$btc_transfert_cost - $usdt_transfert_cost;
        }
        else{
            return (1-$f_buy)*$buying_btc_price*$max_quantity_btc - (1-$f_sell)*$selling_btc_price*$max_quantity_btc;
        }
    }
    
    //Return Fixed cost 
    public function frais_fixes($exch_achat_btc,$exch_vente_btc,$selling_btc_price){
        $btc_transfert_cost = $this->get_exchanges()[$exch_achat_btc]->get_btc_transfert_cost();
        $usdt_transfert_cost = $this->get_exchanges()[$exch_vente_btc]->get_usdt_transfert_cost();
        
        return $selling_btc_price*$btc_transfert_cost + $usdt_transfert_cost;
    }
    
    //Compute security margin of an operation
    public function security_margin($q_alpha, $q_vente_btc, $q_achat_btc){
        return 100*(1 - $q_alpha/min($q_vente_btc,$q_achat_btc));
    }
    
    //Defining the max profitable operation calculation function
    public function max_profitable_operation(){
        //Waiting 30 min since last operation
        /*if(time()-$this->get_date_last_operation() >= $this->get_transfer_duration()){
            $this->set_date_last_operation(0);
        }*/
        //Checking date last operation
        if($this->get_date_last_operation()==0){
            //Updating order books of exchanges
            //$date_m = microtime(true);
            $OB = $this->get_order_books_usdtbtc();
            
            //Display getiing information time
            /*$date_m2 = microtime(true);
            $delta_m2 = $date_m2 - $date_m;
            echo 'time_1 = ';
            echo $delta_m2;
            echo '<br>';*/
            //Display the crossed order book matrix
            //$this->compute_delta_p_matrix($OB);
            
            //Initiate best operation calculation
            $best_profit = 0;
            $best_operation = 0;
            for($i=0;$i<count($OB);$i++){
                for($j=0;$j<count($OB);$j++){
                    if($j!=$i){
                        //For each exchange couple, check if there is interesting operations to compute
                        $interesting_operations = $this->get_incremental_profit(
                                $OB,
                                $i,
                                $j
                            );
                        
                        //Compare with the former best interesting operation 
                        $zzz=0;
                        foreach($interesting_operations["operations"] as $ope){
                            if($best_profit<$ope["cumul_profit"]){
                                $best_profit = $ope["cumul_profit"];
                                $best_operation = $interesting_operations;
                                $best_action = $zzz;
                                $best_i = $i;
                                $best_j = $j;
                                break;
                            }
                            $zzz=$zzz+1;
                        }
                    }
                }
            }

            //Print best operation
            if($best_profit>0){
                $this->print_best_operations($best_operation,$best_action,$best_i,$best_j);    
            }
            else{
                echo 'NO INTERESTING ACTION';
            }

            //Trigger operation lauch if it meets the minimum profit and minimum security margin criterias
            if ($best_profit>$this->get_trigger_profit() and $best_operation["security_marge"]>$this->get_trigger_marge_security()){
                $sell_btc_exchange = $this->get_exchanges()[$best_i]->get_name_exch();
                $buy_btc_exchange = $this->get_exchanges()[$best_j]->get_name_exch();
                $timestamp = time();
                $date = date('jS F Y h:i:s A');
                $this->inserer_bdd(
                    $buy_btc_exchange,
                    $sell_btc_exchange,
                    $best_operation["operations"][$best_action]["sell_price"]-$best_operation["operations"][$best_action]["buy_price"],
                    $best_operation["operations"][$best_action]["cumul_quantity"],
                    $best_operation["operations"][$best_action]["sell_price"],
                    $best_operation["operations"][$best_action]["buy_price"],
                    $best_operation["operations"][$best_action]["cumul_profit"],
                    $timestamp,
                    $best_operation["security_marge"]
                );
                
                //Set date last operation (only to simulate transfert time)
                //$this->set_date_last_operation(time());
            }
            //Display computing time
            /*$date_m3 = microtime(true);
            $delta_m3 = $date_m3 - $date_m2;
            echo '<br>time_2 = ';
            echo $delta_m3;
            echo '<br>';*/
        }
    }
    
    //Function that compute the prospective profit of two different exchanges
    public function get_incremental_profit(
        $orderBook,
        $exch_achat_btc,
        $exch_vente_btc
    ){
        //Initialization
        $i=0;
        $j=0;
        $dataExchAchat = $orderBook[$exch_achat_btc]; //Json of exchange where we should buy
        $dataExchVente = $orderBook[$exch_vente_btc]; //Json of exchange where we should sell
        $quantities_prices_achat = $dataExchAchat->{"sells"}; //Json of low selling orders
        $quantities_prices_vente = $dataExchVente->{"buys"}; //Json of high buying orders
        //We can't sell more than the btc quantity 
        //We admit we have enough $ to buy the max btc sold quantity
        $q_max = $dataExchVente->{"btc_wallet_quantity"};
        //Initialization of the cumulated profit buy subtracting the fixed cost
        $cumul_profit = - $this->frais_fixes(
            $exch_achat_btc,
            $exch_vente_btc,
            1.10*$quantities_prices_achat[0]->{"price"}// 10% margin
        );
        //Initialization of the cumulated btc quantity we should buy and sell during the operation
        $cumul_quantity_bought = 0;
        //Initialization of the array gathering all the interesting operations
        $interesting_operations = [];
        //Initialization of the first buying and selling orders
        $q_achat_btc = $quantities_prices_achat[$i]->{"quantity"};
        $q_vente_btc = $quantities_prices_vente[$j]->{"quantity"};
        
        //While loop : stop when the max btc quantity available is reached, or when we can't overcome the number of buying or selling orders
        while($q_max>0 and $i<count($quantities_prices_achat) and $j<count($quantities_prices_vente)){
            
            //Maximal quantity of btc to buy for the current orders
            $q_alpha = min($q_max,$q_achat_btc,$q_vente_btc);
            
            //Profit calculation for this operation
            $profit = $this->profit_calculation(
                $exch_achat_btc,
                $exch_vente_btc,
                $quantities_prices_achat[$i]->{"price"}, //Low price : we should buy
                $quantities_prices_vente[$j]->{"price"}, //High price : we should sell
                $q_alpha,
                1 //Hors frais fixes
            );
            
            //Keeping profit, quantity bought and operation information
            $cumul_profit = $cumul_profit + $profit;
            $cumul_quantity_bought = $cumul_quantity_bought + $q_alpha;
            $json = array(
                "profit" => $profit,
                "cumul_profit" => $cumul_profit,
                "buy_price" => $quantities_prices_achat[$i]->{"price"},
                "sell_price" => $quantities_prices_vente[$j]->{"price"},
                "quantity" => $q_alpha,
                "cumul_quantity" => $cumul_quantity_bought
            );
            array_push($interesting_operations,$json);
            
            //Three cases to manage : 
            //    - $q_max == 0 => no more btc to sell available => break
            //    - $q_alpha = $q_achat_btc => the $i order is covered, have to move to the $i+1 order, subtract the $q_alpha quantity to the $j order
            //    - $q_alpha = $q_vente_btc => the $j order is covered, have to move to the $j+1 order, subtract the $q_alpha quantity to the $i order
            if($q_alpha == $q_max or $i>=count($quantities_prices_achat)-1 or $j>=count($quantities_prices_vente)-1){
                //Subtract the last operation btc quantity to the max btc quantity available
                $q_max = $q_max - $q_alpha;
                break;
            }
            elseif($q_alpha == $q_achat_btc){
                //Subtract the last operation btc quantity to the max btc quantity available
                $q_max = $q_max - $q_alpha;
                //Moving to the next selling order
                $i=$i+1;
                $q_achat_btc = $quantities_prices_achat[$i]->{"quantity"}; //On passe à l'odre de vente suivant
                $q_vente_btc = $q_vente_btc-$q_alpha; //On enlève la quantité q_alpha à l'odre d'achat
            }
            elseif($q_alpha == $q_vente_btc){
                //Subtract the last operation btc quantity to the max btc quantity available
                $q_max = $q_max - $q_alpha;
                //Moving to the next buying order
                $j=$j+1;
                $q_achat_btc = $q_achat_btc-$q_alpha; //On enlève la quantité q_alpha à l'odre de vente
                $q_vente_btc = $quantities_prices_vente[$j]->{"quantity"}; //On passe à l'odre d'achat suivant
            }
        }
        //Checking the breaking loop reason
        if($q_max==0){
            $json_final = array(
                "security_marge" => $this->security_margin($q_alpha, $q_vente_btc, $q_achat_btc),
                "operations" => $interesting_operations
            );
        }else{
            $json_final = array(
                "security_marge" => 0,
                "operations" => $interesting_operations
            );
        }
        return $json_final;
    }
    
    //Function that display the best operation to compute :
    public function print_best_operations($interesting_operations,$operation,$exch_achat_btc,$exch_vente_btc){
        echo '-------------------- BEST OPERATION --------------------<br>';
        echo 'marge = '.round($interesting_operations["security_marge"],2).'%<br>';
        echo 'acheter btc sur <b>'.$this->get_exchanges()[$exch_achat_btc]->get_name_exch().'</b><br>';
        echo 'vendre btc sur <b>'.$this->get_exchanges()[$exch_vente_btc]->get_name_exch().'</b><br>';
        echo '-------------------------<br>';
        $op = $interesting_operations["operations"][$operation];
        
        echo 'profit : '.$op["profit"].'<br>';
        echo 'cumul_profit : '.$op["cumul_profit"].'<br>';
        echo 'buy_price : '.$op["buy_price"].'<br>';
        echo 'sell_price : '.$op["sell_price"].'<br>';
        echo 'quantity : '.$op["quantity"].'<br>';
        echo 'cumul_quantity : '.$op["cumul_quantity"].'<br><br>';

        echo '<br>-------------------------<br>-------------------------<br><br>';
    }
    
    //Function that display the interesting operations to look at :
    public function print_interesting_operations($interesting_operations,$exch_achat_btc,$exch_vente_btc){
        echo '<br>-------------------------<br>-------------------------<br>Interesting Operations : ';
        echo 'marge = '.round($interesting_operations["security_marge"],2).'%<br>';
        echo 'acheter btc sur <b>'.$this->get_exchanges()[$exch_achat_btc]->get_name_exch().'</b><br>';
        echo 'vendre btc sur <b>'.$this->get_exchanges()[$exch_vente_btc]->get_name_exch().'</b><br>';
        echo '-------------------------<br>';
        $ope = $interesting_operations["operations"];
        foreach($ope as $op){
            echo 'profit : '.$op["profit"].'<br>';
            echo 'cumul_profit : '.$op["cumul_profit"].'<br>';
            echo 'buy_price : '.$op["buy_price"].'<br>';
            echo 'sell_price : '.$op["sell_price"].'<br>';
            echo 'quantity : '.$op["quantity"].'<br>';
            echo 'cumul_quantity : '.$op["cumul_quantity"].'<br><br>';
        }
        echo '<br>-------------------------<br>-------------------------<br><br>';
    }
    
    //Display the order_book exchanges matrix
    public function compute_delta_p_matrix($OB){
        $bloc = [];
        for($i=0;$i<count($OB);$i++){
            for($j=0;$j<count($OB);$j++){
                if($j!=$i){
                    echo 'plateforme_achat_btc = '.$this->get_exchanges()[$i]->get_name_exch().'<br>';
                    echo 'plateforme_vente_btc = '.$this->get_exchanges()[$j]->get_name_exch().'<br>';
                    echo '<table>';
                    if($i != $j){
                        $cumul_sell_quantity = 0;
                        for ($k=0;$k<5;$k++){
                            echo '<tr>';
                            $cumul_buy_quantity=0;
                            $cumul_sell_quantity+=$OB[$i]->{"buys"}[$k]->{'quantity'};
                            for ($l=0;$l<5;$l++){
                                $cumul_buy_quantity+=$OB[$j]->{"sells"}[$l]->{'quantity'};
                                
                                $delta_p = [$OB[$i]->{"buys"}[$k]->{'price'} - $OB[$j]->{"sells"}[$l]->{'price'},$OB[$i]->{"buys"}[$k]->{'price'},$OB[$j]->{"sells"}[$l]->{'price'}];
                                
                                if($delta_p[0]>0){
                                    echo '<td bgcolor="#00FF0">
                                    dp = '.$delta_p[0].'<br>
                                    sell_p : '.$OB[$i]->{"buys"}[$k]->{'price'}.'<br>
                                    buy_p : '.$OB[$j]->{"sells"}[$l]->{'price'}.'<br>
                                    quantity_sell : '.$OB[$i]->{"buys"}[$k]->{'quantity'}.'<br>
                                    quantity_buy : '.$OB[$j]->{"sells"}[$l]->{'quantity'}.'<br> 
                                    cumul_quantity_sell : '.$cumul_sell_quantity.'<br>
                                    cumul_quantity_buy : '.$cumul_buy_quantity.'</td>'; 
                                }
                                else{
                                    echo '<td bgcolor="#FF0000">dp = '.$delta_p[0].'</td>';
                                }
                            }
                            echo '</tr>';
                        }
                    }
                    echo '</table>';    
                    echo '<br><br>';
                }
            }
        }
    }
}