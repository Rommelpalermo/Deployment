<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
requireLogin();

$page_title = 'Computer Parts Compatibility';

// Computer part compatibility data
$compatibility_data = array(
    'cpu' => array(
        'Intel Core i9-13900K' => array(
            'socket' => 'LGA1700',
            'memory_support' => array('DDR4-3200', 'DDR5-5600'),
            'max_memory' => 128,
            'pcie_lanes' => 20,
            'tdp' => 125,
            'recommended_cooler' => 'High-end (240mm+ AIO or high-performance air)',
            'recommended_psu' => 750
        ),
        'Intel Core i7-13700K' => array(
            'socket' => 'LGA1700',
            'memory_support' => array('DDR4-3200', 'DDR5-5600'),
            'max_memory' => 128,
            'pcie_lanes' => 20,
            'tdp' => 125,
            'recommended_cooler' => 'Mid to High-end (120mm+ AIO or tower cooler)',
            'recommended_psu' => 650
        ),
        'Intel Core i5-13600K' => array(
            'socket' => 'LGA1700',
            'memory_support' => array('DDR4-3200', 'DDR5-5600'),
            'max_memory' => 128,
            'pcie_lanes' => 20,
            'tdp' => 125,
            'recommended_cooler' => 'Mid-range (Tower cooler or 120mm AIO)',
            'recommended_psu' => 600
        ),
        'AMD Ryzen 9 7950X' => array(
            'socket' => 'AM5',
            'memory_support' => array('DDR5-5200'),
            'max_memory' => 128,
            'pcie_lanes' => 24,
            'tdp' => 170,
            'recommended_cooler' => 'High-end (280mm+ AIO or premium air)',
            'recommended_psu' => 750
        ),
        'AMD Ryzen 7 7700X' => array(
            'socket' => 'AM5',
            'memory_support' => array('DDR5-5200'),
            'max_memory' => 128,
            'pcie_lanes' => 24,
            'tdp' => 105,
            'recommended_cooler' => 'Mid to High-end (240mm AIO or tower cooler)',
            'recommended_psu' => 650
        ),
        'AMD Ryzen 5 7600X' => array(
            'socket' => 'AM5',
            'memory_support' => array('DDR5-5200'),
            'max_memory' => 128,
            'pcie_lanes' => 24,
            'tdp' => 105,
            'recommended_cooler' => 'Mid-range (Tower cooler or 120mm AIO)',
            'recommended_psu' => 600
        ),
        // Low Specification / Budget CPUs
        'Intel Core i3-12100' => array(
            'socket' => 'LGA1700',
            'memory_support' => array('DDR4-3200', 'DDR5-4800'),
            'max_memory' => 128,
            'pcie_lanes' => 20,
            'tdp' => 60,
            'recommended_cooler' => 'Stock cooler or basic tower',
            'recommended_psu' => 450
        ),
        'AMD Ryzen 5 5600G' => array(
            'socket' => 'AM4',
            'memory_support' => array('DDR4-3200'),
            'max_memory' => 128,
            'pcie_lanes' => 20,
            'tdp' => 65,
            'recommended_cooler' => 'Stock cooler or basic tower',
            'recommended_psu' => 400,
            'integrated_graphics' => 'Radeon Vega 7'
        ),
        'Intel Pentium G7400' => array(
            'socket' => 'LGA1700',
            'memory_support' => array('DDR4-3200', 'DDR5-4800'),
            'max_memory' => 64,
            'pcie_lanes' => 20,
            'tdp' => 46,
            'recommended_cooler' => 'Stock cooler',
            'recommended_psu' => 350
        ),
        'AMD Athlon 3000G' => array(
            'socket' => 'AM4',
            'memory_support' => array('DDR4-2667'),
            'max_memory' => 64,
            'pcie_lanes' => 8,
            'tdp' => 35,
            'recommended_cooler' => 'Stock cooler',
            'recommended_psu' => 300,
            'integrated_graphics' => 'Radeon Vega 3'
        )
    ),
    'motherboard' => array(
        'ASUS ROG STRIX Z790-E' => array(
            'socket' => 'LGA1700',
            'chipset' => 'Z790',
            'memory_slots' => 4,
            'memory_support' => array('DDR5-7800'),
            'max_memory' => 128,
            'pcie_x16_slots' => 2,
            'pcie_x8_slots' => 1,
            'form_factor' => 'ATX',
            'wifi' => true,
            'bluetooth' => true
        ),
        'MSI MAG B660M MORTAR' => array(
            'socket' => 'LGA1700',
            'chipset' => 'B660',
            'memory_slots' => 4,
            'memory_support' => array('DDR4-5066', 'DDR5-4800'),
            'max_memory' => 128,
            'pcie_x16_slots' => 1,
            'pcie_x8_slots' => 1,
            'form_factor' => 'mATX',
            'wifi' => true,
            'bluetooth' => true
        ),
        'ASUS ROG STRIX X670E-E' => array(
            'socket' => 'AM5',
            'chipset' => 'X670E',
            'memory_slots' => 4,
            'memory_support' => array('DDR5-6400'),
            'max_memory' => 128,
            'pcie_x16_slots' => 2,
            'pcie_x8_slots' => 1,
            'form_factor' => 'ATX',
            'wifi' => true,
            'bluetooth' => true
        ),
        'MSI B650M PRO-A' => array(
            'socket' => 'AM5',
            'chipset' => 'B650',
            'memory_slots' => 4,
            'memory_support' => array('DDR5-5200'),
            'max_memory' => 128,
            'pcie_x16_slots' => 1,
            'pcie_x8_slots' => 1,
            'form_factor' => 'mATX',
            'wifi' => false,
            'bluetooth' => false
        ),
        // Low Specification / Budget Motherboards
        'MSI H610M-B' => array(
            'socket' => 'LGA1700',
            'chipset' => 'H610',
            'memory_slots' => 2,
            'memory_support' => array('DDR4-3200'),
            'max_memory' => 64,
            'pcie_x16_slots' => 1,
            'pcie_x8_slots' => 0,
            'form_factor' => 'mATX',
            'wifi' => false,
            'bluetooth' => false
        ),
        'ASRock A520M-HDV' => array(
            'socket' => 'AM4',
            'chipset' => 'A520',
            'memory_slots' => 2,
            'memory_support' => array('DDR4-3200'),
            'max_memory' => 64,
            'pcie_x16_slots' => 1,
            'pcie_x8_slots' => 0,
            'form_factor' => 'mATX',
            'wifi' => false,
            'bluetooth' => false
        ),
        'ASUS PRIME H510M-K' => array(
            'socket' => 'LGA1200',
            'chipset' => 'H510',
            'memory_slots' => 2,
            'memory_support' => array('DDR4-2933'),
            'max_memory' => 64,
            'pcie_x16_slots' => 1,
            'pcie_x8_slots' => 0,
            'form_factor' => 'mATX',
            'wifi' => false,
            'bluetooth' => false
        )
    ),
    'gpu' => array(
        'NVIDIA RTX 4090' => array(
            'power_consumption' => 450,
            'recommended_psu' => 850,
            'pcie_slots' => 3,
            'length' => 336,
            'memory' => 24,
            'memory_type' => 'GDDR6X',
            'pcie_version' => '4.0',
            'recommended_cpu' => array('Intel Core i7-13700K', 'AMD Ryzen 7 7700X')
        ),
        'NVIDIA RTX 4080' => array(
            'power_consumption' => 320,
            'recommended_psu' => 750,
            'pcie_slots' => 2.5,
            'length' => 310,
            'memory' => 16,
            'memory_type' => 'GDDR6X',
            'pcie_version' => '4.0',
            'recommended_cpu' => array('Intel Core i5-13600K', 'AMD Ryzen 5 7600X')
        ),
        'NVIDIA RTX 4070' => array(
            'power_consumption' => 200,
            'recommended_psu' => 650,
            'pcie_slots' => 2,
            'length' => 280,
            'memory' => 12,
            'memory_type' => 'GDDR6X',
            'pcie_version' => '4.0',
            'recommended_cpu' => array('Intel Core i5-13600K', 'AMD Ryzen 5 7600X')
        ),
        'AMD RX 7900 XTX' => array(
            'power_consumption' => 355,
            'recommended_psu' => 800,
            'pcie_slots' => 2.5,
            'length' => 320,
            'memory' => 24,
            'memory_type' => 'GDDR6',
            'pcie_version' => '4.0',
            'recommended_cpu' => array('Intel Core i7-13700K', 'AMD Ryzen 7 7700X')
        ),
        'AMD RX 7800 XT' => array(
            'power_consumption' => 263,
            'recommended_psu' => 700,
            'pcie_slots' => 2,
            'length' => 295,
            'memory' => 16,
            'memory_type' => 'GDDR6',
            'pcie_version' => '4.0',
            'recommended_cpu' => array('Intel Core i5-13600K', 'AMD Ryzen 5 7600X')
        ),
        // Low Specification / Budget GPUs
        'NVIDIA GTX 1650' => array(
            'power_consumption' => 75,
            'recommended_psu' => 450,
            'pcie_slots' => 2,
            'length' => 190,
            'memory' => 4,
            'memory_type' => 'GDDR5',
            'pcie_version' => '3.0',
            'recommended_cpu' => array('Intel Core i3-12100', 'AMD Ryzen 5 5600G')
        ),
        'AMD RX 6500 XT' => array(
            'power_consumption' => 107,
            'recommended_psu' => 500,
            'pcie_slots' => 2,
            'length' => 200,
            'memory' => 4,
            'memory_type' => 'GDDR6',
            'pcie_version' => '4.0',
            'recommended_cpu' => array('Intel Core i3-12100', 'AMD Ryzen 5 5600G')
        ),
        'Intel Arc A380' => array(
            'power_consumption' => 75,
            'recommended_psu' => 450,
            'pcie_slots' => 2,
            'length' => 180,
            'memory' => 6,
            'memory_type' => 'GDDR6',
            'pcie_version' => '4.0',
            'recommended_cpu' => array('Intel Core i3-12100', 'AMD Ryzen 5 5600G')
        ),
        'NVIDIA GT 1030' => array(
            'power_consumption' => 30,
            'recommended_psu' => 300,
            'pcie_slots' => 1,
            'length' => 145,
            'memory' => 2,
            'memory_type' => 'GDDR5',
            'pcie_version' => '3.0',
            'recommended_cpu' => array('Intel Pentium G7400', 'AMD Athlon 3000G')
        )
    ),
    'memory' => array(
        'DDR5-5600 32GB (2x16GB)' => array(
            'type' => 'DDR5',
            'speed' => 5600,
            'capacity' => 32,
            'sticks' => 2,
            'voltage' => 1.25,
            'compatible_cpu' => array('Intel 12th/13th Gen', 'AMD Ryzen 7000 series')
        ),
        'DDR4-3200 32GB (2x16GB)' => array(
            'type' => 'DDR4',
            'speed' => 3200,
            'capacity' => 32,
            'sticks' => 2,
            'voltage' => 1.35,
            'compatible_cpu' => array('Intel 10th/11th/12th/13th Gen', 'AMD Ryzen 1000-5000 series')
        ),
        'DDR5-6000 64GB (2x32GB)' => array(
            'type' => 'DDR5',
            'speed' => 6000,
            'capacity' => 64,
            'sticks' => 2,
            'voltage' => 1.35,
            'compatible_cpu' => array('Intel 12th/13th Gen', 'AMD Ryzen 7000 series')
        ),
        // Low Specification / Budget Memory
        'DDR4-2666 8GB (1x8GB)' => array(
            'type' => 'DDR4',
            'speed' => 2666,
            'capacity' => 8,
            'sticks' => 1,
            'voltage' => 1.2,
            'compatible_cpu' => array('Intel 6th-13th Gen', 'AMD Ryzen 1000-5000 series')
        ),
        'DDR4-3000 16GB (2x8GB)' => array(
            'type' => 'DDR4',
            'speed' => 3000,
            'capacity' => 16,
            'sticks' => 2,
            'voltage' => 1.35,
            'compatible_cpu' => array('Intel 6th-13th Gen', 'AMD Ryzen 1000-5000 series')
        ),
        'DDR4-2400 8GB (2x4GB)' => array(
            'type' => 'DDR4',
            'speed' => 2400,
            'capacity' => 8,
            'sticks' => 2,
            'voltage' => 1.2,
            'compatible_cpu' => array('Intel 6th-13th Gen', 'AMD Ryzen 1000-5000 series')
        )
    ),
    'storage' => array(
        // High Performance Storage
        'Samsung 980 PRO 1TB NVMe SSD' => array(
            'type' => 'NVMe SSD',
            'capacity' => 1024,
            'interface' => 'PCIe 4.0 x4',
            'read_speed' => 7000,
            'write_speed' => 5000,
            'power_consumption' => 6.8
        ),
        'WD Black SN850 500GB NVMe SSD' => array(
            'type' => 'NVMe SSD',
            'capacity' => 512,
            'interface' => 'PCIe 4.0 x4',
            'read_speed' => 7000,
            'write_speed' => 5300,
            'power_consumption' => 7.0
        ),
        // Low Specification / Budget Storage
        'Kingston NV2 500GB NVMe SSD' => array(
            'type' => 'NVMe SSD',
            'capacity' => 512,
            'interface' => 'PCIe 3.0 x4',
            'read_speed' => 3500,
            'write_speed' => 2100,
            'power_consumption' => 4.5
        ),
        'Crucial MX3 250GB SATA SSD' => array(
            'type' => 'SATA SSD',
            'capacity' => 256,
            'interface' => 'SATA III',
            'read_speed' => 560,
            'write_speed' => 510,
            'power_consumption' => 2.0
        ),
        'WD Blue 1TB HDD' => array(
            'type' => 'HDD',
            'capacity' => 1024,
            'interface' => 'SATA III',
            'rpm' => 7200,
            'cache' => 64,
            'power_consumption' => 5.3
        ),
        'Seagate Barracuda 500GB HDD' => array(
            'type' => 'HDD',
            'capacity' => 512,
            'interface' => 'SATA III',
            'rpm' => 7200,
            'cache' => 32,
            'power_consumption' => 4.8
        )
    ),
    'psu' => array(
        // High Performance PSUs
        'Corsair RM850x 850W' => array(
            'wattage' => 850,
            'efficiency' => '80+ Gold',
            'modular' => 'Fully',
            'form_factor' => 'ATX',
            'warranty' => 10
        ),
        'EVGA SuperNOVA 750W' => array(
            'wattage' => 750,
            'efficiency' => '80+ Gold',
            'modular' => 'Fully',
            'form_factor' => 'ATX',
            'warranty' => 10
        ),
        // Low Specification / Budget PSUs
        'EVGA BR 450W' => array(
            'wattage' => 450,
            'efficiency' => '80+ Bronze',
            'modular' => 'Non-modular',
            'form_factor' => 'ATX',
            'warranty' => 3
        ),
        'Corsair CV550 550W' => array(
            'wattage' => 550,
            'efficiency' => '80+ Bronze',
            'modular' => 'Non-modular',
            'form_factor' => 'ATX',
            'warranty' => 3
        ),
        'Thermaltake Smart 500W' => array(
            'wattage' => 500,
            'efficiency' => '80+ White',
            'modular' => 'Non-modular',
            'form_factor' => 'ATX',
            'warranty' => 5
        ),
        'Seasonic Focus GX-650 650W' => array(
            'wattage' => 650,
            'efficiency' => '80+ Gold',
            'modular' => 'Fully',
            'form_factor' => 'ATX',
            'warranty' => 10
        )
    )
);

// Handle compatibility check
$compatibility_result = null;
$recommendations = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['check_compatibility'])) {
    $selected_cpu = isset($_POST['cpu']) ? $_POST['cpu'] : '';
    $selected_motherboard = isset($_POST['motherboard']) ? $_POST['motherboard'] : '';
    $selected_gpu = isset($_POST['gpu']) ? $_POST['gpu'] : '';
    $selected_memory = isset($_POST['memory']) ? $_POST['memory'] : '';
    $selected_storage = isset($_POST['storage']) ? $_POST['storage'] : '';
    $selected_psu = isset($_POST['psu']) ? $_POST['psu'] : '';
    
    $compatibility_result = checkCompatibility($selected_cpu, $selected_motherboard, $selected_gpu, $selected_memory, $selected_storage, $selected_psu, $compatibility_data);
    $recommendations = generateRecommendations($selected_cpu, $selected_gpu, $compatibility_data);
}

function checkCompatibility($cpu, $motherboard, $gpu, $memory, $storage, $psu, $data) {
    $issues = array();
    $compatible = true;
    
    // Check CPU and Motherboard socket compatibility
    if (!empty($cpu) && !empty($motherboard)) {
        $cpu_socket = isset($data['cpu'][$cpu]['socket']) ? $data['cpu'][$cpu]['socket'] : '';
        $mb_socket = isset($data['motherboard'][$motherboard]['socket']) ? $data['motherboard'][$motherboard]['socket'] : '';
        
        if ($cpu_socket !== $mb_socket) {
            $issues[] = array(
                'type' => 'error',
                'message' => "CPU socket ({$cpu_socket}) is not compatible with motherboard socket ({$mb_socket})"
            );
            $compatible = false;
        }
    }
    
    // Check Memory compatibility
    if (!empty($cpu) && !empty($memory)) {
        $cpu_memory_support = isset($data['cpu'][$cpu]['memory_support']) ? $data['cpu'][$cpu]['memory_support'] : array();
        $memory_type = isset($data['memory'][$memory]['type']) ? $data['memory'][$memory]['type'] : '';
        
        $memory_compatible = false;
        foreach ($cpu_memory_support as $supported) {
            if (strpos($supported, $memory_type) !== false) {
                $memory_compatible = true;
                break;
            }
        }
        
        if (!$memory_compatible) {
            $issues[] = array(
                'type' => 'error',
                'message' => "Memory type ({$memory_type}) is not supported by the selected CPU"
            );
            $compatible = false;
        }
    }
    
    // Check GPU and PSU requirements
    if (!empty($gpu) && !empty($cpu)) {
        $gpu_psu_req = isset($data['gpu'][$gpu]['recommended_psu']) ? $data['gpu'][$gpu]['recommended_psu'] : 0;
        $cpu_psu_req = isset($data['cpu'][$cpu]['recommended_psu']) ? $data['cpu'][$cpu]['recommended_psu'] : 0;
        $total_psu_req = $gpu_psu_req + ($cpu_psu_req - 400); // Estimate total system requirement
        
        if ($total_psu_req > 850) {
            $issues[] = array(
                'type' => 'warning',
                'message' => "High power requirements detected. Recommended PSU: {$total_psu_req}W or higher"
            );
        }
    }
    
    // Check GPU CPU bottleneck
    if (!empty($gpu) && !empty($cpu)) {
        $gpu_rec_cpus = isset($data['gpu'][$gpu]['recommended_cpu']) ? $data['gpu'][$gpu]['recommended_cpu'] : array();
        $cpu_compatible = false;
        
        foreach ($gpu_rec_cpus as $rec_cpu) {
            if (strpos($cpu, $rec_cpu) !== false || strpos($rec_cpu, explode(' ', $cpu)[2]) !== false) {
                $cpu_compatible = true;
                break;
            }
        }
        
        if (!$cpu_compatible) {
            $issues[] = array(
                'type' => 'warning',
                'message' => "CPU may not be optimal for the selected GPU. Consider a more powerful processor."
            );
        }
    }
    
    // Check PSU wattage sufficiency
    if (!empty($psu) && (!empty($cpu) || !empty($gpu))) {
        $psu_wattage = isset($data['psu'][$psu]['wattage']) ? $data['psu'][$psu]['wattage'] : 0;
        $total_power_needed = 0;
        
        if (!empty($cpu)) {
            $cpu_psu_req = isset($data['cpu'][$cpu]['recommended_psu']) ? $data['cpu'][$cpu]['recommended_psu'] : 0;
            $total_power_needed = max($total_power_needed, $cpu_psu_req);
        }
        
        if (!empty($gpu)) {
            $gpu_psu_req = isset($data['gpu'][$gpu]['recommended_psu']) ? $data['gpu'][$gpu]['recommended_psu'] : 0;
            $total_power_needed = max($total_power_needed, $gpu_psu_req);
        }
        
        if ($psu_wattage < $total_power_needed) {
            $issues[] = array(
                'type' => 'error',
                'message' => "PSU wattage ({$psu_wattage}W) is insufficient. Minimum required: {$total_power_needed}W"
            );
            $compatible = false;
        } elseif ($psu_wattage < ($total_power_needed * 1.2)) {
            $issues[] = array(
                'type' => 'warning',
                'message' => "PSU wattage is adequate but close to minimum. Consider a higher wattage PSU for better efficiency."
            );
        }
    }
    
    if (empty($issues)) {
        $issues[] = array(
            'type' => 'success',
            'message' => 'All selected components are compatible!'
        );
    }
    
    return array(
        'compatible' => $compatible,
        'issues' => $issues
    );
}

function generateRecommendations($cpu, $gpu, $data) {
    $recommendations = array();
    
    if (!empty($cpu)) {
        $cpu_data = isset($data['cpu'][$cpu]) ? $data['cpu'][$cpu] : array();
        
        // Memory recommendations
        if (isset($cpu_data['memory_support'])) {
            $memory_type = strpos($cpu_data['memory_support'][0], 'DDR5') !== false ? 'DDR5' : 'DDR4';
            $recommendations['memory'] = array(
                'title' => 'Recommended Memory',
                'items' => array(
                    "32GB {$memory_type} (2x16GB) for general use",
                    "64GB {$memory_type} (2x32GB) for professional workloads",
                    "Speed: 3200MHz+ for DDR4, 5200MHz+ for DDR5"
                )
            );
        }
        
        // Cooler recommendations
        if (isset($cpu_data['recommended_cooler'])) {
            $recommendations['cooling'] = array(
                'title' => 'Cooling Requirements',
                'items' => array($cpu_data['recommended_cooler'])
            );
        }
        
        // PSU recommendations
        if (isset($cpu_data['recommended_psu'])) {
            $base_psu = $cpu_data['recommended_psu'];
            $gpu_psu = 0;
            
            if (!empty($gpu)) {
                $gpu_psu = isset($data['gpu'][$gpu]['recommended_psu']) ? $data['gpu'][$gpu]['recommended_psu'] : 0;
            }
            
            $total_psu = max($base_psu, $gpu_psu);
            
            $recommendations['psu'] = array(
                'title' => 'Power Supply',
                'items' => array(
                    "Minimum: {$total_psu}W",
                    "Recommended: " . ($total_psu + 100) . "W for headroom",
                    "80+ Gold certification recommended"
                )
            );
        }
    }
    
    if (!empty($gpu)) {
        $gpu_data = isset($data['gpu'][$gpu]) ? $data['gpu'][$gpu] : array();
        
        // Case size recommendations
        if (isset($gpu_data['length'])) {
            $length = $gpu_data['length'];
            $slots = $gpu_data['pcie_slots'];
            
            $recommendations['case'] = array(
                'title' => 'Case Requirements',
                'items' => array(
                    "GPU clearance: {$length}mm+ length",
                    "PCIe slots: {$slots} slots height",
                    "Good airflow recommended for high-end GPUs"
                )
            );
        }
    }
    
    return $recommendations;
}

include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-microchip"></i> Computer Parts Compatibility Checker</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#helpModal">
            <i class="fas fa-question-circle"></i> Help Guide
        </button>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <!-- Component Selection -->
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="fas fa-cogs"></i> Select Components</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="cpu" class="form-label">
                                    <i class="fas fa-microchip"></i> Processor (CPU)
                                </label>
                                <select class="form-select" id="cpu" name="cpu">
                                    <option value="">Select CPU...</option>
                                    <?php foreach ($compatibility_data['cpu'] as $name => $specs): ?>
                                    <option value="<?php echo $name; ?>" <?php echo (isset($_POST['cpu']) && $_POST['cpu'] == $name) ? 'selected' : ''; ?>>
                                        <?php echo $name; ?> (<?php echo $specs['socket']; ?>, <?php echo $specs['tdp']; ?>W)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="motherboard" class="form-label">
                                    <i class="fas fa-memory"></i> Motherboard
                                </label>
                                <select class="form-select" id="motherboard" name="motherboard">
                                    <option value="">Select Motherboard...</option>
                                    <?php foreach ($compatibility_data['motherboard'] as $name => $specs): ?>
                                    <option value="<?php echo $name; ?>" <?php echo (isset($_POST['motherboard']) && $_POST['motherboard'] == $name) ? 'selected' : ''; ?>>
                                        <?php echo $name; ?> (<?php echo $specs['chipset']; ?>, <?php echo $specs['form_factor']; ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="gpu" class="form-label">
                                    <i class="fas fa-tv"></i> Graphics Card (GPU)
                                </label>
                                <select class="form-select" id="gpu" name="gpu">
                                    <option value="">Select GPU...</option>
                                    <?php foreach ($compatibility_data['gpu'] as $name => $specs): ?>
                                    <option value="<?php echo $name; ?>" <?php echo (isset($_POST['gpu']) && $_POST['gpu'] == $name) ? 'selected' : ''; ?>>
                                        <?php echo $name; ?> (<?php echo $specs['memory']; ?>GB, <?php echo $specs['power_consumption']; ?>W)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="memory" class="form-label">
                                    <i class="fas fa-hdd"></i> Memory (RAM)
                                </label>
                                <select class="form-select" id="memory" name="memory">
                                    <option value="">Select Memory...</option>
                                    <?php foreach ($compatibility_data['memory'] as $name => $specs): ?>
                                    <option value="<?php echo $name; ?>" <?php echo (isset($_POST['memory']) && $_POST['memory'] == $name) ? 'selected' : ''; ?>>
                                        <?php echo $name; ?> (<?php echo $specs['type']; ?>-<?php echo $specs['speed']; ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Storage Selection -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="storage" class="form-label">
                                    <i class="fas fa-save"></i> Storage
                                </label>
                                <select class="form-select" id="storage" name="storage">
                                    <option value="">Select Storage...</option>
                                    <?php foreach ($compatibility_data['storage'] as $name => $specs): ?>
                                    <option value="<?php echo $name; ?>" <?php echo (isset($_POST['storage']) && $_POST['storage'] == $name) ? 'selected' : ''; ?>>
                                        <?php echo $name; ?> (<?php echo $specs['capacity']; ?>GB <?php echo $specs['type']; ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- PSU Selection -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="psu" class="form-label">
                                    <i class="fas fa-bolt"></i> Power Supply (PSU)
                                </label>
                                <select class="form-select" id="psu" name="psu">
                                    <option value="">Select PSU...</option>
                                    <?php foreach ($compatibility_data['psu'] as $name => $specs): ?>
                                    <option value="<?php echo $name; ?>" <?php echo (isset($_POST['psu']) && $_POST['psu'] == $name) ? 'selected' : ''; ?>>
                                        <?php echo $name; ?> (<?php echo $specs['wattage']; ?>W <?php echo $specs['efficiency']; ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-center">
                        <button type="submit" name="check_compatibility" class="btn btn-primary btn-lg">
                            <i class="fas fa-search"></i> Check Compatibility
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Compatibility Results -->
        <?php if ($compatibility_result): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="fas fa-clipboard-check"></i> Compatibility Results</h5>
            </div>
            <div class="card-body">
                <?php foreach ($compatibility_result['issues'] as $issue): ?>
                <div class="alert alert-<?php 
                    echo $issue['type'] == 'error' ? 'danger' : 
                         ($issue['type'] == 'warning' ? 'warning' : 'success'); 
                ?>" role="alert">
                    <i class="fas fa-<?php 
                        echo $issue['type'] == 'error' ? 'exclamation-triangle' : 
                             ($issue['type'] == 'warning' ? 'exclamation-circle' : 'check-circle'); 
                    ?>"></i>
                    <?php echo $issue['message']; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Recommendations -->
        <?php if (!empty($recommendations)): ?>
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-lightbulb"></i> Recommendations</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($recommendations as $category => $rec): ?>
                    <div class="col-md-6 mb-3">
                        <h6><i class="fas fa-<?php 
                            echo $category == 'memory' ? 'memory' : 
                                 ($category == 'cooling' ? 'snowflake' : 
                                 ($category == 'psu' ? 'bolt' : 'cube')); 
                        ?>"></i> <?php echo $rec['title']; ?></h6>
                        <ul class="list-unstyled">
                            <?php foreach ($rec['items'] as $item): ?>
                            <li><i class="fas fa-check text-success me-2"></i><?php echo $item; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="col-lg-4">
        <!-- Component Details -->
        <div class="card mb-4">
            <div class="card-header">
                <h6><i class="fas fa-info-circle"></i> Component Information</h6>
            </div>
            <div class="card-body" id="component-details">
                <p class="text-muted">Select components to view detailed specifications and compatibility information.</p>
            </div>
        </div>
        
        <!-- Quick Tips -->
        <div class="card">
            <div class="card-header">
                <h6><i class="fas fa-tips"></i> Quick Tips</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6><i class="fas fa-exclamation-triangle text-warning"></i> Socket Compatibility</h6>
                    <p class="small text-muted">Always ensure CPU and motherboard sockets match (LGA1700, AM5, etc.)</p>
                </div>
                
                <div class="mb-3">
                    <h6><i class="fas fa-bolt text-info"></i> Power Requirements</h6>
                    <p class="small text-muted">High-end GPUs require powerful PSUs. Always add 20% headroom.</p>
                </div>
                
                <div class="mb-3">
                    <h6><i class="fas fa-thermometer-half text-danger"></i> Cooling</h6>
                    <p class="small text-muted">High TDP processors need adequate cooling solutions.</p>
                </div>
                
                <div class="mb-3">
                    <h6><i class="fas fa-ruler text-success"></i> Physical Space</h6>
                    <p class="small text-muted">Check case clearance for large GPUs and CPU coolers.</p>
                </div>
                
                <div>
                    <h6><i class="fas fa-memory text-primary"></i> Memory Speed</h6>
                    <p class="small text-muted">Match memory type and speed with CPU/motherboard specifications.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Help Modal -->
<div class="modal fade" id="helpModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Compatibility Checker Help</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6>How to Use</h6>
                <ol>
                    <li>Select components from the dropdown menus</li>
                    <li>Click "Check Compatibility" to analyze your selection</li>
                    <li>Review compatibility results and recommendations</li>
                    <li>Make adjustments based on the feedback</li>
                </ol>
                
                <h6 class="mt-4">Understanding Results</h6>
                <ul>
                    <li><span class="badge bg-danger">Error</span> - Critical incompatibility that must be resolved</li>
                    <li><span class="badge bg-warning">Warning</span> - Potential issues or suboptimal pairing</li>
                    <li><span class="badge bg-success">Success</span> - Components are fully compatible</li>
                </ul>
                
                <h6 class="mt-4">Key Compatibility Factors</h6>
                <ul>
                    <li><strong>Socket Type:</strong> CPU and motherboard must have matching sockets</li>
                    <li><strong>Memory Type:</strong> DDR4 vs DDR5 support varies by platform</li>
                    <li><strong>Power Supply:</strong> Must meet total system power requirements</li>
                    <li><strong>Physical Clearance:</strong> GPU length and cooler height restrictions</li>
                    <li><strong>Performance Balance:</strong> Avoid CPU/GPU bottlenecks</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// Component data for dynamic display
var componentData = <?php echo json_encode($compatibility_data); ?>;

document.addEventListener('DOMContentLoaded', function() {
    var selects = ['cpu', 'motherboard', 'gpu', 'memory', 'storage', 'psu'];
    
    for (var i = 0; i < selects.length; i++) {
        var selectId = selects[i];
        document.getElementById(selectId).addEventListener('change', function() {
            updateComponentDetails();
        });
    }
    
    function updateComponentDetails() {
        var detailsDiv = document.getElementById('component-details');
        var selectedComponents = {};
        
        selects.forEach(function(selectId) {
            var select = document.getElementById(selectId);
            if (select.value) {
                selectedComponents[selectId] = {
                    name: select.value,
                    data: componentData[selectId][select.value]
                };
            }
        });
        
        if (Object.keys(selectedComponents).length === 0) {
            detailsDiv.innerHTML = '<p class="text-muted">Select components to view detailed specifications and compatibility information.</p>';
            return;
        }
        
        var html = '';
        
        Object.keys(selectedComponents).forEach(function(type) {
            var component = selectedComponents[type];
            html += '<div class="mb-3">' +
                '<h6><i class="fas fa-' + getIconForType(type) + '"></i> ' + component.name + '</h6>' +
                '<div class="small">';
            
            // Display relevant specifications
            Object.keys(component.data).forEach(function(key) {
                var value = component.data[key];
                if (typeof value === 'object') {
                    html += '<div><strong>' + formatKey(key) + ':</strong> ' + (Array.isArray(value) ? value.join(', ') : JSON.stringify(value)) + '</div>';
                } else {
                    html += '<div><strong>' + formatKey(key) + ':</strong> ' + value + '</div>';
                }
            });
            
            html += '</div></div>';
        });
        
        detailsDiv.innerHTML = html;
    }
    
    function getIconForType(type) {
        var icons = {
            'cpu': 'microchip',
            'motherboard': 'memory',
            'gpu': 'tv',
            'memory': 'hdd'
        };
        return icons[type] || 'cog';
    }
    
    function formatKey(key) {
        return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    }
    
    // Initialize if components are already selected
    updateComponentDetails();
});
</script>

<style>
.timeline-item {
    border-left: 2px solid #e9ecef;
    padding-left: 15px;
    margin-left: 10px;
    position: relative;
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: -5px;
    top: 5px;
    width: 8px;
    height: 8px;
    background-color: #007bff;
    border-radius: 50%;
}

.alert {
    border-left: 4px solid;
}

.alert-danger {
    border-left-color: #dc3545;
}

.alert-warning {
    border-left-color: #ffc107;
}

.alert-success {
    border-left-color: #28a745;
}
</style>

<?php include 'includes/footer.php'; ?>