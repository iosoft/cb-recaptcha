## How to change reCAPTCHA colors ##

**Important: this method is not officially supported and may break when new versions of reCAPTCHA system will come out.**


You can apply CSS styles to reCaptcha widget to change its colors and other visual properties. These instructions show you how.


Set the reCAPTCHA theme to **clean** by adding the following code to your site. The **clean** theme is much more neutral than the default one and much easier to integrate with site's look and feel.

```
  <script>
  var RecaptchaOptions = {
     theme : 'clean'
  };
  </script>
```

Then add the following instructions to your CSS file. Remember to replace **#FF0000** with colors of your choice.

```
  .recaptchatable .recaptcha_image_cell, #recaptcha_table {
    background-color:'''#FF0000''' !important; ''//reCaptcha widget background color''
  }
  
  #recaptcha_table {
    border-color: '''#FF0000''' !important; ''//reCaptcha widget border color''
  }
  
  #recaptcha_response_field {
    border-color: '''#FF0000''' !important; ''//Text input field border color''
    background-color:'''#FF0000''' !important; ''//Text input field background color''
  }
```

Obviously you can add any other CSS property. Check any CSS tutorial for more information.

**Enjoy**.

[Source](http://wiki.recaptcha.net/index.php/How_to_change_reCAPTCHA_colors)