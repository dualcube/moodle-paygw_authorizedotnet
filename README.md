# Authorize.Net Payment Gateway for Moodle

## Introduction
This is a payment gateway plugin to integrate **Authorize.Net** with Moodle, allowing users to pay for courses securely via credit/debit cards.  

## About Authorize.Net
[Authorize.Net](https://www.authorize.net/) is a trusted U.S.-based payment gateway service provider that enables merchants to accept online payments through credit cards, debit cards, and electronic checks. It is widely used by businesses and e-commerce platforms around the world.  

## Features
- Integrates Authorize.Net payment gateway with Moodle.  
- Secure payment processing with API credentials (Login ID & Transaction Key).  
- Supports multiple currencies (depending on your Authorize.Net merchant account).  
- Works seamlessly with Moodle's **Enrolment on Payment** method.  

## Installation
1. Download the plugin ZIP file from the GitHub repository or Moodle Plugin Directory.  
2. Extract the ZIP file and copy the folder into `/payment/gateway/` directory of your Moodle installation.  
   - Alternatively, install it using Moodle’s **Install Plugin** option in the Site Administration panel.  
3. Complete installation by visiting **Site Administration → Notifications** in Moodle.  

## How to Use
1. Register for an [Authorize.Net Merchant Account](https://account.authorize.net/).  
2. Log in to the [Authorize.Net Merchant Dashboard](https://account.authorize.net/) and generate your **API Login ID** and **Transaction Key**.  
3. Configure the Authorize.Net payment gateway in Moodle with your credentials.  
4. Go to the course where you want to enable paid enrolment.  
5. Add the **Enrolment on Payment** method and select **Authorize.Net** as the gateway.  
6. Set your preferred currency (based on your Authorize.Net account support).  

## Sandbox Testing
To test your integration, you can use the **Authorize.Net Sandbox** environment:  
- Sign up at [https://sandbox.authorize.net/](https://sandbox.authorize.net/)  
- Generate sandbox **API Login ID** and **Transaction Key**.  
- Switch Moodle plugin settings to use Sandbox mode for testing transactions.  

## Support
If you encounter issues, please open an issue in the official GitHub repository:  
👉 [GitHub Issues](https://github.com/dualcube/moodle-paygw_authorizedotnet/issues)  

## Author
**DualCube Team**  
🌐 Website: [https://dualcube.com](https://dualcube.com)  
📧 Email: admin@dualcube.com  

## License
This plugin is released under the [GNU GPL v3](http://www.gnu.org/copyleft/gpl.html).  
