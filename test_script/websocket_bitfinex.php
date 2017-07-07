<script type="text/javascript">
    var order_book = 0;
    
    wss = new WebSocket('wss://api.bitfinex.com/ws/2')

    wss.onmessage = function(msg) {
        console.log('taille_order_book : ' + order_book.length);
        console.log('taille_order_book : ' + order_book.length);
        console.log('massage recu : ' + msg.data);
        console.log('orders_book : ' +order_book);

        msg_parse = JSON.parse(msg.data);
        data = msg_parse[1];

        if(data.length>10){
            order_book = data;
            remplir_table();
        }
        else{
            update_table(data);
            remplir_table();
        }
        console.log(' ')
    }
    
    wss.onopen = function(event) {
        var data = {
              "event": "subscribe",
              "channel": "book",
              "symbol": "tBTCUSD",
              "prec": "P0",
              "freq": "F0",
              "len": 25
        };
        wss.send(JSON.stringify(data));
}

    wss.onerror = function(error){
         console.log('error : ' + error);
}

    function remplir_table(){
        i_buys=0;
        i_sells=0;
        console.log('length : ' + order_book.length);
        for(k=0;k<order_book.length;k++){
            order = order_book[k];
            if(order[2]>0){//Si le prix renvoyé est positif
                document.getElementById('ligne_'+i_buys+'colonne_0').innerHTML = order[0];    
                document.getElementById('ligne_'+i_buys+'colonne_1').innerHTML = order[2];    
                document.getElementById('ligne_'+i_buys+'colonne_2').innerHTML = order[1];
                i_buys = i_buys+1;
            }
            else{
                document.getElementById('ligne_'+i_sells+'colonne_3').innerHTML = order[0];    
                document.getElementById('ligne_'+i_sells+'colonne_4').innerHTML = -order[2];    
                document.getElementById('ligne_'+i_sells+'colonne_5').innerHTML = order[1];
                i_sells = i_sells+1;
            }
        }
}

    function update_table(order_update){
        reshape_order_book();
        price = order_update[0];
        count = order_update[1];
        amount = order_update[2];
        if(count>0){
            addOrUpdate(order_update);
        }
        else{
            if(amount==1){
                remove(order_update);
            }
            else{
                remove(order_update);
            }
        }
    }

    function addOrUpdate(order_update){
        prix = order_update[0];
        count = order_update[1];
        quantity = order_update[2];
        if(quantity>0){
            for(i=0;i<order_book.length;i++){
                order = order_book[i];
                if(order[0]==prix && order[2]>0){
                    order_book[i]=order_update;
                    break;
                }
                else if(order[0]<prix && order[2]>0){
                    for (k=order_book.length;k>i;k--){
                        order_book[k]=order_book[k-1];
                    }
                    order_book[i]=order_update;
                    break;
                }
            }
        }
        else{
            for(i=0;i<order_book.length;i++){
                order = order_book[i];
                if(order[0]==prix && order[2]<0){
                    order_book[i]=order_update;
                    break;
                }
                else if(order[0]>prix && order[2]<0){
                    for (k=order_book.length;k>i;k--){
                        order_book[k]=order_book[k-1];
                    }
                    order_book[i]=order_update;
                    break;
                }
            }
        }
    }
    
    function remove(order_remove){
        prix = order_remove[0];
        count = order_remove[1];
        quantity = order_remove[2];
        for(i=0;i<order_book.length;i++){
            order = order_book[i];
            if(order[0]==prix){
                for (k=i;k<order_book.length-1;k++){
                    order_book[k]=order_book[k+1]
                }
            }
        }
    }
    
    function reshape_order_book(){
        order_book = order_book.slice(0, 50);
    }
</script>

<style>
    table, th, td {
    border: 1px solid black;
}

</style>

<html>
    <body>
        <table style="width:50%;text-align:center">
            <tr>
                <th>Buys_price</th><th>Buys_quantity</th><th>Buys_count</th><th>Sells_prices</th><th>Sells_quantity</th><th>Sells_count</th>
            </tr>
            <?php 
            for ($i=0;$i<25;$i++){
                echo '<tr id="ligne_'.$i.'">';
                for ($j=0;$j<6;$j++){
                    echo '<td id="ligne_'.$i.'colonne_'.$j.'">';
                    echo '0-0';
                    echo '</td>';
                }
                echo '</tr>';
            }
            ?>
        </table>
    </body>
</html>
