<?php

namespace Database\Seeders;

use App\Models\Cliente;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use RuntimeException;
use Illuminate\Support\Carbon;
use App\Models\user;

class ClienteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if(app()->isProduction()){
            throw new RuntimeException("ClienteSeeder solo puede ejecutarse fuera de produccion");
        }

        $clientes = [
            ['nombre'=>"Maria", 'apellido'=>"Elizabethe", 'telefono'=>'52341469','foto'=> '/clientesSeedersPictures/25369714.jpg'],
            ['nombre'=>"Jorge", 'apellido'=>"Tomaz", 'telefono'=>'55364127','foto'=> '/clientesSeedersPictures/26341258.jpg'],
            ['nombre'=>"Rigoberto", 'apellido'=>"Manuel", 'telefono'=>'32463978','foto'=> '/clientesSeedersPictures/36428971.jpg'],
            ['nombre'=>"Sofia", 'apellido'=>"Elizabethe", 'telefono'=>'85796912','foto'=> '/clientesSeedersPictures/36452879.jpg'],
            ['nombre'=>"Lizethe", 'apellido'=>"Monica", 'telefono'=>'20146397','foto'=> '/clientesSeedersPictures/43697825.jpg'],
            ['nombre'=>"Diego", 'apellido'=>"Perez", 'telefono'=>'55472139','foto'=> '/clientesSeedersPictures/45287936.jpg'],
            ['nombre'=>"Samuel", 'apellido'=>"Jose", 'telefono'=>'63982413','foto'=> '/clientesSeedersPictures/52364718.jpg'],
            ['nombre'=>"Tulipa", 'apellido'=>"Montez", 'telefono'=>'45236978','foto'=> '/clientesSeedersPictures/52364782.jpg'],
            ['nombre'=>"Felipe", 'apellido'=>"Montez", 'telefono'=>'23648297','foto'=> '/clientesSeedersPictures/52783914.jpg']
        ];

        foreach($clientes as $index => $cliente){
            $user = new User();
            /*el formato que se esta utilizando para las contraseñas es:
                primeras dos letras en minusculas del nombre
                primeras dos letras en minusculas del apelldio
                primeros dos numeros del telefono
                primer letra en mayuscula del nombre
                primer letra en mayuscula del apellido
                @*/
            $user->forceFill([
                'name' => $cliente['nombre'].$cliente['apellido'],
                'email' => strtolower($cliente['nombre']).strtolower($cliente['apellido']).now()->timestamp.'@gmail.com',
                'password' => 
                    strtolower(substr($cliente['nombre'],0,2)).
                    strtolower(substr($cliente['apellido'],0,2)).
                    substr($cliente['telefono'],0,2).
                    strtoupper(substr($cliente['nombre'],0,1)).
                    strtoupper(substr($cliente['apellido'],0,1)).
                    '@',
                'email_verified_at' => now(),
                'iscustomer'=>true,
                'googlepassword'=>false,
                'created_at' => now(),
                'updated_at' => now()
            ])->save();

            $clienteParameters = [
                    'user_id' => $user->id,
                    'nombre' => $cliente['nombre'],
                    'apellido' => $cliente['apellido'],
                    'telefono' => $cliente['telefono'],
                    'comment' => '',
                    'assessment' => rand(0,100),
                    'fecharegistro' => now(),
                    'foto'=>$cliente['foto'],
                    'created_at'=>now(),
                    'updated_at'=>now()
            ];
            $cliente = new Cliente();
            $cliente->forceFill($clienteParameters)->save();
        }
    }
}
