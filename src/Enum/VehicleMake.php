<?php
declare(strict_types=1);

namespace App\Enum;

enum VehicleMake: string
{
    case TOYOTA = 'TOYOTA';
    case HONDA = 'HONDA';
    case FORD = 'FORD';
    case CHEVROLET = 'CHEVROLET';
    case NISSAN = 'NISSAN';
    case HYUNDAI = 'HYUNDAI';
    case KIA = 'KIA';
    case VOLKSWAGEN = 'VOLKSWAGEN';
    case BMW = 'BMW';
    case MERCEDES_BENZ = 'MERCEDES_BENZ';
    case AUDI = 'AUDI';
    case PORSCHE = 'PORSCHE';
    case LEXUS = 'LEXUS';
    case INFINITI = 'INFINITI';
    case MAZDA = 'MAZDA';
    case SUBARU = 'SUBARU';
    case JEEP = 'JEEP';
    case DODGE = 'DODGE';
    case RAM = 'RAM';
    case GMC = 'GMC';
    case CADILLAC = 'CADILLAC';
    case BUICK = 'BUICK';
    case TESLA = 'TESLA';
    case VOLVO = 'VOLVO';
    case LAND_ROVER = 'LAND_ROVER';
    case JAGUAR = 'JAGUAR';
    case MINI = 'MINI';
    case FIAT = 'FIAT';
    case ALFA_ROMEO = 'ALFA_ROMEO';
    case PEUGEOT = 'PEUGEOT';
    case CITROEN = 'CITROEN';
    case RENAULT = 'RENAULT';
    case SKODA = 'SKODA';
    case SEAT = 'SEAT';
    case OPEL = 'OPEL';
    case VAUXHALL = 'VAUXHALL';
    case SUZUKI = 'SUZUKI';
    case MITSUBISHI = 'MITSUBISHI';
    case GENESIS = 'GENESIS';
    case POLESTAR = 'POLESTAR';
    case BYD = 'BYD';
    case GEELY = 'GEELY';
    case GREAT_WALL = 'GREAT_WALL';
    case MG = 'MG';
    case DACIA = 'DACIA';
    case ACURA = 'ACURA';
    case ASTON_MARTIN = 'ASTON_MARTIN';
    case BENTLEY = 'BENTLEY';
    case ROLLS_ROYCE = 'ROLLS_ROYCE';
    case FERRARI = 'FERRARI';
    case LAMBORGHINI = 'LAMBORGHINI';
    case MASERATI = 'MASERATI';
    case BUGATTI = 'BUGATTI';
    case DS = 'DS';
    case CUPRA = 'CUPRA';
    case ALPINE = 'ALPINE';
    case SMART = 'SMART';
    case SAAB = 'SAAB';
    case LADA = 'LADA';
    case TATA = 'TATA';
    case MAHINDRA = 'MAHINDRA';
    case CHERY = 'CHERY';
    case PROTON = 'PROTON';
    case PERODUA = 'PERODUA';
    case DAEWOO = 'DAEWOO';
    case SSANGYONG = 'SSANGYONG';
    case HAVAL = 'HAVAL';
    case CHANGAN = 'CHANGAN';
    case DONGFENG = 'DONGFENG';
    case HONGQI = 'HONGQI';
    case RIVIAN = 'RIVIAN';
    case LUCID = 'LUCID';
    case NIO = 'NIO';
    case XPENG = 'XPENG';
    case GAC_AION = 'GAC_AION';

    public function label(): string
    {
        return match ($this) {
            self::MERCEDES_BENZ => 'Mercedes-Benz',
            self::LAND_ROVER => 'Land Rover',
            self::ALFA_ROMEO => 'Alfa Romeo',
            self::CITROEN => 'Citroën',
            self::ROLLS_ROYCE => 'Rolls-Royce',
            default => ucwords(strtolower(str_replace('_', ' ', $this->name)))
        };
    }

    public function models(): array
    {
        return match ($this) {
            self::TOYOTA => ['Camry','Corolla','RAV4','Highlander','Prius','Yaris'],
            self::HONDA => ['Civic','Accord','CR-V','Pilot','Fit','HR-V'],
            self::FORD => ['Focus','Fusion','Escape','Explorer','F-150','Mustang'],
            self::CHEVROLET => ['Malibu','Cruze','Equinox','Traverse','Silverado','Camaro'],
            self::NISSAN => ['Altima','Sentra','Rogue','Murano','Leaf','Pathfinder'],
            self::HYUNDAI => ['Elantra','Sonata','Tucson','Santa Fe','Kona','Ioniq'],
            self::KIA => ['Rio','Cerato','Sportage','Sorento','Seltos','Stinger'],
            self::VOLKSWAGEN => ['Golf','Passat','Tiguan','Polo','Jetta','T-Roc'],
            self::BMW => ['1 Series','3 Series','5 Series','7 Series','X3','X5'],
            self::MERCEDES_BENZ => ['A-Class','C-Class','E-Class','S-Class','GLC','GLE'],
            self::AUDI => ['A3','A4','A6','Q3','Q5','Q7'],
            self::PORSCHE => ['Macan','Cayenne','911','Panamera','Taycan'],
            self::LEXUS => ['IS','ES','NX','RX','UX','GX'],
            self::INFINITI => ['Q50','Q60','QX50','QX60','QX80'],
            self::MAZDA => ['Mazda3','Mazda6','CX-5','CX-30','CX-9','MX-5'],
            self::SUBARU => ['Impreza','Legacy','Forester','Outback','Crosstrek','WRX'],
            self::JEEP => ['Renegade','Compass','Cherokee','Grand Cherokee','Wrangler','Gladiator'],
            self::DODGE => ['Charger','Challenger','Durango','Journey'],
            self::RAM => ['1500','2500','3500','ProMaster'],
            self::GMC => ['Terrain','Acadia','Yukon','Sierra 1500'],
            self::CADILLAC => ['CT5','XT4','XT5','XT6','Escalade'],
            self::BUICK => ['Encore','Envision','Enclave'],
            self::TESLA => ['Model 3','Model S','Model X','Model Y'],
            self::VOLVO => ['S60','S90','XC40','XC60','XC90'],
            self::LAND_ROVER => ['Range Rover','Discovery','Defender','Range Rover Sport','Evoque'],
            self::JAGUAR => ['XE','XF','F-Pace','E-Pace','I-Pace'],
            self::MINI => ['Cooper','Countryman','Clubman'],
            self::FIAT => ['500','Panda','Tipo','500X'],
            self::ALFA_ROMEO => ['Giulia','Stelvio','Giulietta','Tonale'],
            self::PEUGEOT => ['208','308','3008','5008','508'],
            self::CITROEN => ['C3','C4','C5 Aircross','Berlingo'],
            self::RENAULT => ['Clio','Megane','Captur','Kadjar','Arkana'],
            self::SKODA => ['Fabia','Octavia','Superb','Karoq','Kodiaq'],
            self::SEAT => ['Ibiza','Leon','Arona','Ateca','Tarraco'],
            self::OPEL => ['Corsa','Astra','Insignia','Grandland X','Mokka'],
            self::VAUXHALL => ['Corsa','Astra','Insignia','Grandland','Mokka'],
            self::SUZUKI => ['Swift','Baleno','Vitara','S-Cross','Jimny'],
            self::MITSUBISHI => ['Outlander','Eclipse Cross','ASX','L200'],
            self::GENESIS => ['G70','G80','G90','GV70','GV80'],
            self::POLESTAR => ['2'],
            self::BYD => ['Atto 3','Tang','Han','Seal','Dolphin'],
            self::GEELY => ['Emgrand','Coolray','Azkarra'],
            self::GREAT_WALL => ['Haval H2','Haval H6','Jolion'],
            self::MG => ['ZS','HS','MG5','MG4','Hector'],
            self::DACIA => ['Duster','Sandero','Logan','Jogger','Spring'],
            self::ACURA => ['ILX','TLX','RDX','MDX','Integra'],
            self::ASTON_MARTIN => ['DB11','DB12','Vantage','DBX'],
            self::BENTLEY => ['Continental GT','Flying Spur','Bentayga'],
            self::ROLLS_ROYCE => ['Phantom','Ghost','Wraith','Cullinan'],
            self::FERRARI => ['488','F8 Tributo','Roma','Portofino','SF90'],
            self::LAMBORGHINI => ['Huracán','Aventador','Urus'],
            self::MASERATI => ['Ghibli','Quattroporte','Levante','Grecale'],
            self::BUGATTI => ['Chiron','Divo','La Voiture Noire'],
            self::DS => ['DS 3','DS 4','DS 7','DS 9'],
            self::CUPRA => ['Leon','Ateca','Formentor','Born'],
            self::ALPINE => ['A110'],
            self::SMART => ['Fortwo','Forfour'],
            self::SAAB => ['9-3','9-5'],
            self::LADA => ['Niva','Vesta','Granta'],
            self::TATA => ['Tiago','Altroz','Nexon','Harrier'],
            self::MAHINDRA => ['Thar','Scorpio','XUV700','Bolero'],
            self::CHERY => ['Tiggo 4','Tiggo 7','Tiggo 8','Arrizo 5'],
            self::PROTON => ['Saga','Persona','X70','X50'],
            self::PERODUA => ['Myvi','Bezza','Ativa'],
            self::DAEWOO => ['Matiz','Lanos','Nubira'],
            self::SSANGYONG => ['Tivoli','Korando','Rexton'],
            self::HAVAL => ['H2','H6','Jolion'],
            self::CHANGAN => ['CS55','CS75','UNI-T'],
            self::DONGFENG => ['Aeolus AX7','Fengshen S30'],
            self::HONGQI => ['H9','HS5','E-HS9'],
            self::RIVIAN => ['R1T','R1S'],
            self::LUCID => ['Air'],
            self::NIO => ['ES6','ET7','EC6'],
            self::XPENG => ['P7','G3','G9'],
            self::GAC_AION => ['Aion S','Aion Y','Aion LX'],
        };
    }
}