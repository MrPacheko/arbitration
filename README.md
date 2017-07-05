# arbitration
Crypto Currency Exchange Arbitration Project

Ce document détaille la méthode d’arbitrage des différentes plateformes d’échange de crypto monnaies, comme Bitfinex, Poloniex ou Bittrex. L’arbitrage consiste à exploiter les différences de cours sur ces trois plateformes. Prenons un exemple concret : 

Le btc se vend 2000$ sur la plateforme A et 2500$ sur la plateforme B. On achète 2000$ de btc sur A et on les revend sur B pour 2500$. On génère ainsi 500$ de profit.

En pratique, cette opération est difficile puisqu’il faut transiter les btc d’une plateforme à l’autre avant que les cours ne se rééquilibre. Actuellement, il faut 20 à 30 min réaliser un transfert, ce qui est beaucoup trop long. 
De plus, des frais sont prélevés lors des achats et ventes sur les plateformes et lors des transferts de token. Ces coûts sont à prendre en compte dans le calcul du profit.


## Méthode utilisée

Nous illustrerons la méthode par un cas d’application avec deux plateformes A et B, et un cours USDT_BTC.

Pour contourner le problème du temps de transfert des monnaies d’une plateforme à l’autre, il faut se donner les moyens de réaliser instantanément les actions de vente et d’achat de monnaie lorsque les cours sont déséquilibrés. Pour cela, on se dote de 2 wallets par plateforme, un wallet en USDT et un wallet en BTC.

<p align="center">
  <img src="https://github.com/martmull/arbitration/blob/master/images/tab_1.png"/>
</p>

Si p_achat_A , le prix de vente du btc sur la plateforme A est plus grand que p_vente_B, le prix d’achat sur la plateforme B, alors il y a besoin d’arbitrage. L’opération consiste à vendre les btc du Wallet_BTC_A et à an acheter sur la plateforme B. On a ainsi :

<p align="center">
  <img src="https://github.com/martmull/arbitration/blob/master/images/tab_2.png"/>
</p>

L’étape suivante consiste à transférer les btc et usdt de manière à retrouver la situation initiale. Ainsi le transfert de monnaie se réalise après l’achat et la vente, réglant le problème de délai de transfert. On récupère alors le bénéfice p_achat_A - p_vente_B . Et on peut recommencer l’opération.

<p align="center">
  <img src="https://github.com/martmull/arbitration/blob/master/images/tab_3.png"/>
</p>

Prise en compte des frais

La dernière explication est incomplète, il manque la prise en compte des différentes ponctions occasionnées par l’achat, la vente et les transfert de monnaies.

Pour modéliser ce problème, on se donne différentes grandeurs utiles :

<p align="center">
  <img src="https://github.com/martmull/arbitration/blob/master/images/tab_4.png"/>
</p>

Remarque : ces valeurs peuvent différer entre les plateformes A et B.