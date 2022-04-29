<?php

namespace App\Constant;

class SecurityConstant
{
    /*** Registration*/
    public const success_send_confirmation_email = "Un email de vérification vient d'être envoyer sur votre boite mail.";

    public const error_send_confirmation_email = "Aucun compte trouvé, vérifié vos identifiants.";

    public const success_register = "Votre compte a été créé avec success, un email de vérification vient d'être envoyer sur votre boite mail.";

    public const error_user_unknown = "Aucun compte trouvé, vérifié vos identifiants.";

    public const error_unknown = "Error inconnus. veuillez essayer ultérieurement ou contactez l'administrateur.";

    public const error_user_banned = "Votre compte est verrouillé. Pour plus d'information veuillez contacter l'administrateur.";

    public const error_email_already_verified = "Votre compte a déjà été vérifié.";

    public const success_send_retrieve_password_email = "Un email de pour changer votre password vient d'être envoyé.";

    public const success_retrieve_password = "Votre password a été modifier avec succès.";


    /*** Message Flash */
    public const flash_type_error = "error";
    public const flash_type_success = "success";
}